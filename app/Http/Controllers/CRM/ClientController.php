<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\Models\CRM\Alumni;
use App\Models\CRM\Client;
use App\Models\CRM\Editor;
use App\Models\CRM\Mentor;
use App\Models\CRM\StudentMentor;
use App\Models\Education;
use App\Models\Roles;
use App\Models\StudentMentors;
use App\Models\Students;
use App\Models\SynchronizeLogs;
use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Faker\Generator as Faker;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

        $sync_log = new SynchronizeLogs;
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
            $sync_log->status = 'failed';
            $sync_log->message = $e->getMessage();
            return !$automated ? response()->json(['success' => false, 'error' => $e->getMessage()]) : 0;
        }

        $sync_log->status = 'success';
        $sync_log->message = 'The data has been synced';
        $sync_log->save();
    
        return !$automated ? response()->json(['success' => true, 'data' => $data]) : 1;
    }

    public function recap_alumni($isNull = true, $paginate = "no")
    {
        $alumnis = $educations = array();
        $alumni = Alumni::with('student', 'student.school')->
        when(!$isNull, function ($query) {
                $query->whereHas('student', function($q1) {
                    $q1->where(DB::raw("CONCAT(`st_firstname`, ' ', `st_last_name`)"), '!=', '')->where('st_mail', '!=', '')->where('st_mail', '!=', '-')->whereNull('st_mail');
                });
            })->distinct()->get();
        foreach ($alumni as $data) {
            $find = User::where('email', $data->st_mail)->count();
            if ($find == 0) {

                $alumnis[] = array(
                    'first_name' => $this->remove_blank($data->student->st_firstname),
                    'last_name' => $this->remove_blank($data->student->st_lastname),
                    'phone_number' => $this->remove_blank($data->student->st_phone),
                    'email' => $this->remove_blank($data->student->st_mail),
                    'email_verified_at' => $data->student->st_mail === '' ? null : Carbon::now(),
                    'password' => $this->remove_blank($data->student->st_password),
                    'status' => 1,
                    'is_verified' => $data->student->st_mail === '' ? 0 : 1,
                    'remember_token' => null,
                    'profile_picture' => null,
                    'imported_id' => $this->remove_blank($data->student->st_id),
                    'position' => null,
                    'imported_from' => 'u5794939_allin_bd',
                    'educations' => !empty($data->student->school->sch_name) ? array(
                        'graduated_from' => $data->student->school->sch_name,
                        'major' => null,
                        'degree' => null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'graduation_date' => $data->alu_graduatedate
                    ) : null
                );
            }
        }

        return $paginate == "yes" ? $this->helper->paginate($alumnis) : $alumnis;
    }

    public function import_alumni()
    {
        $bulk_data = $this->recap_alumni(false);
        DB::beginTransaction();
        try {
            foreach ($bulk_data as $alumni_data) {
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

                if ($alumni_data['educations'] != null) {
                    Education::insert(
                        ['user_id' => $alumni->id] + $alumni_data['educations']
                    );
                }


                $alumni->roles()->attach($alumni->id, ['role_id' => 4, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Import Data Student Issue : '.$e->getMessage());
            throw New Exception('Failed to import students data');
        }

        return $bulk_data;
    }

    public function recap_editor($isNull = true, $paginate = "no")
    {
        $editors = array();
        $editor_crm = Editor::where('status', 1)->where(function($query) {
            $query->whereNotNull('email')->orwhere('email', '!=', '');
        })->get();
        foreach ($editor_crm as $data) {
            // $find = User::where('email', $data->email)->first();

            // if (!$find) { //if email sudah ada di database
                
                $editors[] = array(
                    'first_name' => $this->remove_blank($data->first_name),
                    'last_name' => $this->remove_blank($data->last_name),
                    'phone_number' => $this->remove_blank($data->phone),
                    'email' => $this->remove_blank($data->email),
                    'email_verified_at' => $data->email === '' ? null : Carbon::now(),
                    'password' => $this->remove_blank($data->password),
                    'status' => $data->status,
                    'is_verified' => $data->email === '' ? 0 : 1,
                    'remember_token' => null,
                    'profile_picture' => null,
                    'imported_id' => null,
                    'position' => null,
                    'imported_from' => 'u5794939_editing',
                    'educations' => !empty($data->graduated_from) ? array(
                        'graduated_from' => $data->graduated_from,
                        'major' => $data->major,
                        'degree' => null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'graduation_date' => null
                    ) : null
                );
            // }
            
        }

        return $paginate == "yes" ? $this->helper->paginate($editors) : $editors;
    }

    public function import_editor()
    {
        $new_roles = $editors = array();
        $bulk_data = $this->recap_editor(false);
        DB::beginTransaction();
        try {
            foreach ($bulk_data as $editor_data) {

                if ($user = User::where('email', $editor_data['email'])->first()) {
                    $id = $user->id;
                    if (UserRoles::where('user_id', $id)->where('role_id', 3)->count() == 0) {
                        $new_roles[] = array(
                            'email' => $user->email
                        );
                        $user->roles()->attach($id, ['role_id' => 3, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
                    }
                    break;
                }

                $editor = new User;
                $editor->first_name = $editor_data['first_name'];
                $editor->last_name = $editor_data['last_name'];
                $editor->phone_number = $editor_data['phone_number'];
                $editor->email = $editor_data['email'];
                $editor->email_verified_at = $editor_data['email_verified_at'];
                $editor->password = $editor_data['password'];
                $editor->status = $editor_data['status'];
                $editor->is_verified = $editor_data['is_verified'];
                $editor->remember_token = $editor_data['remember_token'];
                $editor->profile_picture = $editor_data['profile_picture'];
                $editor->imported_id = $editor_data['imported_id'];
                $editor->position = $editor_data['position'];
                $editor->imported_from = $editor_data['imported_from'];
                $editor->save();

                $editors = $editor;

                if ($editor_data['educations'] != null) {
                    Education::insert(
                        ['user_id' => $editor->id] + $editor_data['educations']
                    );
                }

                $editor->roles()->attach($editor->id, ['role_id' => 3, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Import Data Editor Issue : '.$e->getMessage());
            throw New Exception('Failed to import editor data');
        }

        return array(
            'new_roles' => $new_roles,
            'editors' => $editors
        );
    }

    public function recap_mentor($isNull = true, $paginate = "no")
    {
        $mentors = array();

        $email_empty = Mentor::where(function($query) {
            $query->where('mt_email', '=', '')->orwhere('mt_email', '=', '-')->orwhereNull('mt_email');
        })->where('mt_status', 1)->select('mt_email')->get();

        $mentor_crm = Mentor::with('university')->where(function($query) use ($email_empty) {
            $query->whereNotNull('mt_email')->whereNotIn('mt_email', $email_empty);
        })->where('mt_status', 1)->get();

        foreach ($mentor_crm as $data) {
            $find = User::where('imported_id', $data->mt_id)->first();
            if (!$find) {
                $mentors[] = array(
                    'first_name' => $this->remove_blank($data->mt_firstn),
                    'last_name' => $this->remove_blank($data->mt_lastn),
                    'phone_number' => $this->remove_blank($data->mt_phone),
                    'email' => $this->remove_blank($data->mt_email),
                    'email_verified_at' => $data->mt_mail === '' ? null : Carbon::now(),
                    'password' => $this->remove_blank($data->mt_password),
                    'status' => 1,
                    'is_verified' => $data->mt_mail === '' ? 0 : 1,
                    'remember_token' => null,
                    'profile_picture' => null,
                    'imported_id' => $this->remove_blank($data->mt_id),
                    'position' => null,
                    'imported_from' => 'u5794939_allin_bd',
                    'educations' => !empty($data->university->univ_name) ? array(
                        'graduated_from' => $data->university->univ_name,
                        'major' => $data->major,
                        'degree' => null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'graduation_date' => null
                    ) : null
                );
            }
        }

        return $paginate == "yes" ? $this->helper->paginate($mentors) : $mentors;
    }

    public function import_mentor()
    {
        $new_roles = array();
        $bulk_data = $this->recap_mentor(false);
        DB::beginTransaction();
        try {
            foreach ($bulk_data as $mentor_data) {
                if ($user = User::where('email', $mentor_data['email'])->first()) {
                    $id = $user->id;
                    if (UserRoles::where('user_id', $id)->where('role_id', 2)->count() == 0) {
                        $new_roles[] = array(
                            'email' => $user->email
                        );
                        $user->roles()->attach($id, ['role_id' => 2, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
                    }
                    continue;
                }

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

                if ($mentor_data['educations'] != null) {
                    Education::insert(
                        ['user_id' => $mentor->id] + $mentor_data['educations']
                    );
                }

                $mentor->roles()->attach($mentor->id, ['role_id' => 2, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Import Data Mentor Issue : '.$e->getMessage());
            throw New Exception('Failed to import mentor data');
        }

        return 'Mentor data has been imported';
    }

    public function recap_student($isNull = true, $paginate = "no")
    {
        $students = array();
        $alumni = Alumni::select('st_id')->get();
        $client_empty_mail = Client::select('st_mail')->where('st_mail', '=', '')->orWhere('st_mail', '=', '-')->orWhereNull('st_mail')->get();

        $client = Client::with('student_programs.student_mentors')->whereHas('student_programs.programs', function($query) {
            $query->where('prog_main', 'Admissions Mentoring');
        })->withAndWhereHas('student_programs', function ($query) {
            $query->where('stprog_status', 1);
        })->whereNotIn('st_id', $alumni)->when(!$isNull, function ($query) {
                $query->where(function($q1) {
                    $q1->where(DB::raw("CONCAT(`st_firstname`, ' ', `st_lastname`)"), '!=', '')->where('st_mail', '!=', '')->where('st_mail', '!=', '-')->whereNotNull('st_mail');
                });
        })->where(function($query) use ($client_empty_mail) {
            $query->whereNotNull('st_mail')->orWhere('st_mail', '!=', '')->whereNotIn('st_mail', $client_empty_mail);
        })->distinct()->get();

        $index = 0;
        foreach ($client->unique('st_mail') as $client_data) {

            $find = Students::where('email', $client_data->st_mail)->count();
            if ($find == 0) { // if there are no data with client data email then save record

                $students[$index] = array(
                    'first_name' => $client_data->st_firstname,
                    'last_name' => $client_data->st_lastname,
                    'birthday' => $this->remove_invalid_date($client_data->st_dob),
                    'phone_number' => $client_data->st_phone,
                    // 'grade' => isset($client_data->school->sch_level) ? $this->remove_string_grade($client_data->school->sch_level) : null,
                    'grade' => $client_data->st_grade,
                    'email' => $this->remove_blank($client_data->st_mail),
                    'email_verified_at' => $client_data->st_mail != null ? Carbon::now() : null,
                    'address' => $this->remove_blank($client_data->st_address, 'text'),
                    'city' => $this->remove_blank($client_data->st_city),
                    'password' => $this->remove_blank($client_data->st_password),
                    'imported_from' => 'u5794939_allin_bd',
                    'imported_id' => $this->remove_blank($client_data->st_id, 'text'),
                    'status' => 1,
                    'is_verified' => $client_data->st_mail == '' ? 0 : 1,
                    'created_at' => $client_data->st_datecreate,
                    'updated_at' => $client_data->st_datelastedit,
                    'school_name' => isset($client_data->school->sch_name) ? ($client_data->school->sch_name == '-' ? null : $client_data->school->sch_name) : null,
                    'mentor_1' => null,
                    'mentor_2' => null
                );

                foreach ($client_data->student_programs as $client_programs) {
                    foreach ($client_programs->student_mentors as $client_mentors) {
                        $students[$index]['mentor_1'] = $client_mentors->mt_id1;
                        $students[$index]['mentor_2'] = $client_mentors->mt_id2;
                    }
                }
            $index++;
            }
        }

        return $paginate == "yes" ? $this->helper->paginate($students) : $students;
    }

    public function import_student()
    {
        //* get students data that we want to import from big data to mentoring
        $students = $this->recap_student(false);

        DB::beginTransaction();
        try {
            
            // Students::insert($students);
            foreach ($students as $in_student) {
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
                $inserted_student_id = $student->id;
                
                //* insert mentor_1 from bigdata into student mentors
                $student_mentor_1 = new StudentMentors;
                $student_mentor_1->student_id = $inserted_student_id;
                if (($in_student['mentor_1'] != "") || ($in_student['mentor_1'] != NULL)) {
                    $mentor_data_1 = User::where('imported_id', $in_student['mentor_1']);
                    if ($mentor_data_1->count() > 0) {

                        $mentor_id_1 = $mentor_data_1->first()->id;
                        $student_mentor_1->user_id = $mentor_id_1;
                        $student_mentor_1->save();
                    } else {
                        $student_mentor_1->imported_id = $in_student['mentor_1'];
                        $student_mentor_1->save();
                    }
                }

                //* insert mentor_2 from bigdata into student mentors
                $student_mentor_2 = new StudentMentors;
                $student_mentor_2->student_id = $inserted_student_id;
                if (($in_student['mentor_2'] != "") || ($in_student['mentor_2'] != NULL)) {
                    $mentor_data_2 = User::where('imported_id', $in_student['mentor_2']);
                    if ($mentor_data_2->count() > 0) {

                        $mentor_id_2 = $mentor_data_2->first()->id;
                        $student_mentor_2->user_id = $mentor_id_2;
                        $student_mentor_2->save();
                    } else {
                        $student_mentor_2->imported_id = $in_student['mentor_2'];
                        $student_mentor_2->save();
                    }
                }
                
            }
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            // return response()->json($e->getMessage());
            throw New Exception('Failed to import students data');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Import Data Student Issue : '.$e->getMessage());
            throw New Exception('Failed to import students data');
            // return response()->json($e->getMessage());
        }

        return 'Students data has been imported';
    }

    public function insert_student_mentor($stmentor, $inserted_student_id = null)
    {
        foreach ($stmentor as $client_mentor) {
            $student_id = Students::where('email', $client_mentor['st_mail'])->first()->id;
            if ( ($client_mentor['mentor_1'] != "") || ($client_mentor['mentor_1'] != NULL) ) {
                $mentor_id_1 = User::where('imported_id', $client_mentor['mentor_1'])->first()->id;
                if (StudentMentors::where('student_id', $student_id)->where('user_id', $mentor_id_1)->count() == 0) {
                    $student_mentor[] = array(
                        'student_id' => $student_id,
                        'user_id' => $mentor_id_1,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    );
                } 
            }

            if ( ($client_mentor['mentor_2'] != "") || ($client_mentor['mentor_2'] != NULL) ) {
                $mentor_id_2 = User::where('imported_id', $client_mentor['mentor_2'])->first()->id;
                if (StudentMentors::where('student_id', $student_id)->where('user_id', $mentor_id_2)->count() == 0) {
                    $student_mentor[] = array(
                        'student_id' => $student_id,
                        'user_id' => $mentor_id_2,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    );
                }
            }
        }

        return StudentMentors::insert($student_mentor);
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
