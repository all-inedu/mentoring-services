<?php

namespace App\Http\Controllers\CRM\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\Models\CRM\Editor;
use App\Models\CRM\Mentor;
use App\Models\Education;
use App\Models\Students;
use App\Models\SynchronizeLogs;
use App\Models\User;
use App\Models\UserRoles;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperController;       
    }
    
    public function synchronize($role, $type, $automated = false)
    {
        $request = [
            'role' => $role,
            'type' => $type
        ];

        $rules = [
            'role' => 'required|in:student,mentor,editor,alumni',
            'type' => 'required|in:sync,import'
        ];

        $validator = Validator::make($request, $rules);
        if ($validator->fails()) {
            return !$automated ? response()->json(['success' => false, 'error' => $validator->errors()], 400) : 0;
        }

        $sync_log = new SynchronizeLogs();
        $sync_log->user_type = $role;

        try {
            switch (strtolower($role)) {
                case "student": 
                    $data = $type == "sync" ? $this->recap_student(false, "yes") : $this->import_student();
                    break;
                
                case "mentor":
                    $data = $type == "sync" ? $this->recap_mentor(false, "yes") : $this->import_mentor();
                    break;

                case "editor":
                    $data = $type == "sync" ? $this->recap_editor(false, "yes") : $this->import_editor();
                    break;

                case "alumni":
                    $data = $type == "sync" ? $this->recap_alumni(false, "yes") : $this->import_alumni();
                    break;
            }
        } catch (Exception $e) {

            echo $e->getMessage().' on line '.$e->getLine();exit;
            $sync_log->status = 'failed';
            $sync_log->message = $e->getMessage();
            return !$automated ? response()->json(['success' => false, 'error' => $e->getMessage()]) : 0;
        }

        $sync_log->status = 'success';
        $sync_log->message = 'The data has been synced';
        $sync_log->save();
    
        return !$automated ? response()->json(['success' => true, 'data' => $data]) : 1;
    }

    public function recap_student($isNull = true, $paginate = "no")
    {
        $crm_host = '127.0.0.1:8000';
        $response = Http::get($crm_host.'/api/v1/get/mentees');
        $crm_mentees = collect(json_decode($response)->data);

        $local_clients_fullname = Students::select(DB::raw('(CASE WHEN last_name IS NULL THEN first_name ELSE CONCAT(first_name, " ", last_name) END) as fullname'))->pluck('fullname')->toArray();
        $local_clients_mail = Students::pluck('email')->toArray();

        $new_mentees = $crm_mentees->whereNotIn('full_name', $local_clients_fullname)->whereNotIn('mail', $local_clients_mail);

        foreach ($new_mentees as $key => $new_mentee) {
            if (count($new_mentee->client_program[0]->client_mentor) > 0) {

                $collection[$key] = [
                    'first_name' => $new_mentee->first_name,
                    'last_name' => $new_mentee->last_name,
                    'birthday' => $this->remove_invalid_date($new_mentee->dob),
                    'phone_number' => $new_mentee->phone,
                    'grade' => $new_mentee->st_grade,
                    'email' => $this->remove_blank($new_mentee->mail),
                    'email_verified_at' => null,
                    'address' => $this->remove_blank($new_mentee->address, 'text'),
                    'city' => $this->remove_blank($new_mentee->city),
                    'password' => $this->remove_blank($new_mentee->st_password),
                    'imported_from' => 'u5794939_allin_bd_v2_3',
                    'imported_id' => $this->remove_blank($new_mentee->st_id, 'text'),
                    'status' => 1,
                    'is_verified' => $new_mentee->mail == '' ? 0 : 1,
                    'created_at' => $new_mentee->created_at,
                    'updated_at' => $new_mentee->updated_at,
                    'school_name' => $new_mentee->school_name,
                    'total_mentor' => count($new_mentee->client_program[0]->client_mentor)
                ];

                $idx_mentor = 1;
                foreach ($new_mentee->client_program[0]->client_mentor as $client_mentor) {
                    $field_name = 'mentor_'.$idx_mentor;
                    $collection[$key][$field_name] = $client_mentor->roles[0]->pivot->extended_id;

                    $idx_mentor++;
                } 
            }

        }

        return $paginate == "yes" ? $this->helper->paginate($collection) : $collection;
    }

    public function import_student()
    {
        //* get students data that we want to import from big data v2 to mentoring
        $students = $this->recap_student(false);
        
        DB::beginTransaction();
        try {
            
            foreach ($students as $in_student) {

                if ($in_student['email'] == null)
                    continue;

                # create new student
                $student = new Students;
                $student->first_name = $in_student['first_name'];
                $student->last_name = $in_student['last_name'];
                $student->birthday = $in_student['birthday'];
                $student->phone_number = $in_student['phone_number'];
                $student->grade = $in_student['grade'];
                $student->email = $in_student['email'];
                $student->email_verified_at = $in_student['email_verified_at'];
                $student->address = $in_student['address'];
                $student->city = $in_student['city'];
                $student->password = $in_student['password'];
                $student->imported_from = $in_student['imported_from'];
                $student->imported_id = $in_student['imported_id'];
                $student->status = $in_student['status'];
                $student->is_verified = $in_student['is_verified'];
                $student->created_at = $in_student['created_at'];
                $student->updated_at = $in_student['updated_at'];
                $student->school_name = $in_student['school_name'];
                $student->save();

                echo 'Saved student ID : '.$student->id;
            
                //* make a relation between mentor and student from bigdata into student mentors
                $i = 0;
                while ($i < $in_student['total_mentor']) {

                    //* when mentor doesn't exist
                    //* user has to import mentor first
                    if (!$mentor_profile = User::where('imported_id', $in_student['mentor_'.$i+1])->first()) 
                        continue;

                    $mentor_profileId = $mentor_profile->id;
                    
                    if ($student->student_mentors()->where('user_id', $mentor_profileId)->count() == 0) {
                        $student->users()->attach($mentor_profileId, ['priority' => $i+1]);
                    }

                    $i++;
                }
                
            }
            DB::commit();

        } catch (QueryException $e) {

            DB::rollBack();
            echo $e->getMessage().' on line '.$e->getLine();exit;
            Log::error('Import student failed : '. $e->getMessage());
            throw New Exception('Failed to import students data');

        } catch (Exception $e) {
            
            DB::rollBack();
            echo $e->getMessage().' on line '.$e->getLine();exit;
            Log::error('Import Data Student Issue : '.$e->getMessage());
            throw New Exception('Failed to import students data');
        }

        return 'Students data has been imported';
    }

    public function recap_mentor($isNull = true, $paginate = "no")
    {
        $crm_host = '127.0.0.1:8000';
        $response = Http::get($crm_host.'/api/v1/get/mentors');
        $crm_mentors = collect(json_decode($response)->data);

        
        $local_mentors = User::whereHas('roles', function ($subQuery) {
            $subQuery->where('role_name', 'mentor');
        })->pluck('imported_id')->toArray();
        
        // echo json_encode($crm_mentors->whereNotIn('roles.*.pivot.extended_id', $local_mentors));exit;

        $array_crm_mentorId = $crm_mentors->map(function ($item) {
            foreach ($item->roles as $role) {
                return $role->pivot->extended_id;
            }
        })->toArray();

        # compare the crm_mentors with local_mentors
        $diff = array_diff($array_crm_mentorId, $local_mentors);
        
        # fetch the diff
        # and put it into the array 2D
        $array_diff_mentorId = [];
        foreach ($diff as $key => $value) {
            array_push($array_diff_mentorId, $value);
        }
        
        foreach ($crm_mentors as $key => $value) {

            $imported_id = $this->remove_blank($value->roles[0]->pivot->extended_id);
            if (!in_array($imported_id, $array_diff_mentorId))
                continue;

            $collection[$key] = [
                'first_name' => $this->remove_blank($value->first_name),
                'last_name' => $this->remove_blank($value->last_name),
                'phone_number' => $this->remove_blank($value->phone),
                'email' => $this->remove_blank($value->email),
                'email_verified_at' => $value->email_verified_at,
                'password' => $this->remove_blank($value->password),
                'status' => $value->active,
                'is_verified' => $value->email_verified_at === null ? 0 : 1,
                'remember_token' => null,
                'profile_picture' => null,
                'imported_id' => $this->remove_blank($value->roles[0]->pivot->extended_id),
                'position' => null,
                'imported_from' => 'allin_bd_v2'
            ];

            foreach ($value->educations  as $education) {
                $collection[$key]['educations'][] = [
                    'graduated_from' => $education->univ_name,
                    'major' => $education->major_name,
                    'degree' => $education->pivot->degree,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'graduation_date' => $education->pivot->graduation_date
                ];
            }

        }

        return $paginate == "yes" ? $this->helper->paginate($collection) : $collection;    
    }

    public function import_mentor()
    {
        $mentors = $this->recap_mentor(false);
        DB::beginTransaction();
        try {

            foreach ($mentors as $mentor_data) {

                $mentor = new User;
                $mentor->first_name = $mentor_data['first_name'];
                $mentor->last_name = $mentor_data['last_name'];
                $mentor->phone_number = $mentor_data['phone_number'];
                $mentor->email = $mentor_data['email'];
                $mentor->email_verified_at = $mentor_data['email_verified_at'];
                $mentor->password = $mentor_data['password'];
                $mentor->status = $mentor_data['status'];
                $mentor->is_verified = $mentor_data['is_verified'];
                $mentor->remember_token = $mentor_data['remember_token'];
                $mentor->profile_picture = $mentor_data['profile_picture'];
                $mentor->imported_id = $mentor_data['imported_id'];
                $mentor->position = $mentor_data['position'];
                $mentor->imported_from = $mentor_data['imported_from'];
                $mentor->save();

                echo 'Saved Mentor ID : '.$mentor->imported_id. ' punya id : '.$mentor->id;

                # add the education information
                if (count($mentor_data['educations']) > 0) {

                    foreach ($mentor_data['educations'] as $education) {
                        $detailEducation = [
                            'user_id' => $mentor->id
                        ] + $education;
                        DB::table('education')->insert($detailEducation);
                    }
                }

                # set roles 'mentor' for new mentor
                $mentor->roles()->attach(2);
            }

            DB::commit();

        } catch (Exception $e) {
            
            DB::rollBack();
            echo $e->getMessage().' on line '.$e->getLine();exit;
            Log::error('Import Data Mentor Issue : '.$e->getMessage());
            throw New Exception('Failed to import mentor data');
        }

        return 'Mentor data has been imported';
    }

    public function recap_alumni($isNull = true, $paginate = "no")
    {
        $crm_host = '127.0.0.1:8000';
        $response = Http::get($crm_host.'/api/v1/get/alumnis');
        $crm_alumnis = collect(json_decode($response)->data);

        $local_alumni_email = User::pluck('email')->toArray();

        $new_alumnis = $crm_alumnis->whereNotIn('mail', $local_alumni_email);

        foreach ($new_alumnis as $key => $new_alumni) {

            $collection[$key] = [
                'first_name' => $new_alumni->first_name,
                'last_name' => $new_alumni->last_name,
                'phone_number' => $new_alumni->phone,
                'grade' => $new_alumni->st_grade,
                'email' => $this->remove_blank($new_alumni->mail),
                'email_verified_at' => null,
                'password' => $this->remove_blank($new_alumni->st_password),
                'status' => 1,
                'is_verified' => $new_alumni->mail == '' ? 0 : 1,
                'address' => $this->remove_blank($new_alumni->address, 'text'),
                'remember_token' => null,
                'profile_picture' => null,
                'imported_id' => $this->remove_blank($new_alumni->st_id, 'text'),
                'position' => null,
                'imported_from' => 'u5794939_allin_bd_v2',
            ];          

            if ($new_alumni->school_name) {
                $collection[$key]['educations'] = [
                    'graduated_from' => $new_alumni->school_name,
                    'major' => null,
                    'degree' => null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'graduation_date' => null
                ];
            }

        }

        return $paginate == "yes" ? $this->helper->paginate($collection) : $collection;
    
    }

    public function import_alumni()
    {
        $alumnis = $this->recap_alumni(false);

        DB::beginTransaction();
        try {

            foreach ($alumnis as $alumni_data) {

                $alumni = new User;
                $alumni->first_name = $alumni_data['first_name'];
                $alumni->last_name = $alumni_data['last_name'];
                $alumni->phone_number = $alumni_data['phone_number'];
                $alumni->email = $alumni_data['email'];
                $alumni->email_verified_at = $alumni_data['email_verified_at'];
                $alumni->password = $alumni_data['password'];
                $alumni->status = $alumni_data['status'];
                $alumni->is_verified = $alumni_data['is_verified'];
                $alumni->remember_token = $alumni_data['remember_token'];
                $alumni->profile_picture = $alumni_data['profile_picture'];
                $alumni->imported_id = $alumni_data['imported_id'];
                $alumni->position = $alumni_data['position'];
                $alumni->imported_from = $alumni_data['imported_from'];
                $alumni->save();

                echo 'Saved Alumni ID : '.$alumni->imported_id. ' punya id : '.$alumni->id;

                # add the education information
                if (in_array('educations', $alumni_data)) {

                    // foreach ($alumni_data['educations'] as $education) {
                        $detailEducation = [
                            'user_id' => $alumni->id
                        ] + $alumni_data['educations'];
                        DB::table('education')->insert($detailEducation);
                    // }
                }

                $alumni->roles()->attach(4, [
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now()
                ]);

            }
            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            echo $e->getMessage().' on line '.$e->getLine();exit;
            Log::error('Import Data Student Issue : '.$e->getMessage());
            throw New Exception('Failed to import students data');
        }

        return $alumnis;
    }

    //** HELPER */

    public function remove_invalid_date($data)
    {
        if ($data) {
            $date = explode('-', $data);
            $month = $date[1];
            $day = $date[2];
            $year = $date[0];
    
            return checkdate($month, $day, $year) ? $data : null;
        }
    }

    public function remove_blank($data)
    {
        return (empty($data) || ($data == '-')) ? null : $data;
    }

    public function remove_string_grade($grade)
    {
        $output = preg_replace( '/[^0-9]/', '', $grade );
        return $output;
    }
}
