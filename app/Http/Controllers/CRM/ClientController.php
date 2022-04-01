<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Alumni;
use App\Models\CRM\Client;
use App\Models\CRM\Editor;
use App\Models\CRM\Mentor;
use App\Models\Education;
use App\Models\Roles;
use App\Models\Students;
use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Faker\Generator as Faker;

class ClientController extends Controller
{
    
    public function synchronize($role, $type)
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
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        try {
            switch ($role) {
                case "student": 
                    $data = $type == "sync" ? $this->recap_student(false) : $this->import_student();
                    break;
                
                case "mentor":
                    $data = $type == "sync" ? $this->recap_mentor(false) : $this->import_mentor();
                    break;

                case "editor":
                    $data = $type == "sync" ? $this->recap_editor(false) : $this->import_editor();
                    break;

                case "alumni":
                    $data = $type == "sync" ? $this->recap_alumni(false) : $this->import_alumni();
                    break;
            }
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function recap_alumni($isNull = true)
    {  
        $alumnis = array();
        $alumni = Alumni::with('student.school')->get();
        foreach ($alumni as $data) {
            $alumnis[] = array(
                'first_name' => $data->student->st_firstname,
                'last_name' => $data->student->st_lastname,
                'phone_number' => $data->student->st_phone,
                'email' => $data->student->st_mail,
                'email_verified_at' => $data->student->st_mail === '' ? null : Carbon::now(),
                'password' => $data->student->st_password,
                'status' => 1,
                'is_verified' => $data->student->st_mail === '' ? 0 : 1,
                'remember_token' => null,
                'profile_picture' => null,
                'imported_id' => $data->student->st_id,
                'position' => null,
                'imported_from' => 'u5794939_allin_bd'       
            );

            $educations[] = array(
                'user_id' => '',
                'graduated_from' => $data->student->school->sch_name,
                'graduation_date' => $data->alugraduatedate
            );
        }

        return $alumnis;
        // return array_map($this->map, $alumnis);
    }

    public function import_alumni()
    {
        
    }

    public function recap_editor()
    {
        $editors = array();
        $editor_crm = Editor::all();
        foreach ($editor_crm as $data) {
            $find = User::where('email', $data->email)->first();

            if ($find) { //if email sudah ada di database
                if (!UserRoles::where('user_id', $find->id)->where('role_id', 3)->first()) { //check if email role tidak sama dengan editor

                    $find->position = $data->position;
                    $find->save();

                    $role = new UserRoles;
                    $role->user_id = $find->id;
                    $role->role_id = 3;
                    // $role->save();
                }
            } else if (!$find) {
                $editor = new User;
                $editor->first_name = $data->first_name;
                $editor->last_name = $data->last_name == '' ? null : $data->last_name;
                $editor->phone_number = $data->phone == '' ? null : $data->phone;
                $editor->email = $data->email == '' ? null : $data->email;
                $editor->email_verified_at = $data->email == '' ? null : Carbon::now();
                $editor->password = $data->password == '' ? null : $data->password;
                $editor->status = $data->status;
                $editor->is_verified = $data->email == '' ? 0 : 1;
                $editor->remember_token = null;
                $editor->profile_picture = null;
                $editor->imported_id = null;
                $editor->position = $data->position;
                // $editor->save();

                $role = new UserRoles;
                $role->user_id = $editor->id;
                $role->role_id = 3;
                // $role->save();

                $editors[] = $editor;
            }
            
        }

        return $editors;
    }

    public function import_editor()
    {
        
    }

    public function recap_mentor($isNull = true)
    {
        $mentors = array();
        $mentor_crm = Mentor::with('university')->get();
        foreach ($mentor_crm as $data) {
            $find = User::where('email', $data->email)->first();

            if ($find) { //if email sudah ada di database
                if (!UserRoles::where('user_id', $find->id)->where('role_id', 2)->first()) { //check if email role tidak sama dengan editor

                    $find->imported_id = $data->mt_id;
                    // $find->save();

                    if ($data->univ_id != '') {
                        $education = new Education;
                        $education->user_id = $find->id;
                        $education->graduated_from = $data->university->univ_name;
                        $education->major = $data->mt_major;
                        $education->degree = null;
                        // $education->save();
                    }

                    $role = new UserRoles;
                    $role->user_id = $find->id;
                    $role->role_id = 2;
                    // $role->save();
                }
            } else if ($find == 0) {
                $mentor = new User;
                $mentor->first_name = $data->mt_firstn;
                $mentor->last_name = $data->mt_lastn == '' ? null : $data->mt_lastn;
                $mentor->phone_number = $data->mt_phone == '' ? null : $data->mt_phone;
                $mentor->email = $data->mt_email == '' ? null : $data->mt_email;
                $mentor->email_verified_at = $data->mt_email == '' ? null : Carbon::now();
                $mentor->password = $data->mt_password == '' ? null : $data->mt_password;
                $mentor->status = 1;
                $mentor->is_verified = $data->mt_email == '' ? 0 : 1;
                $mentor->remember_token = null;
                $mentor->profile_picture = null;
                $mentor->imported_id = $data->mt_id;
                // $mentor->save();

                if ($data->univ_id != '') {
                    $education = new Education;
                    $education->user_id = $mentor->id;
                    $education->graduated_from = $data->university->univ_name;
                    $education->major = $data->mt_major;
                    $education->degree = null;
                    // $education->save();
                }

                $role = new UserRoles;
                $role->user_id = $mentor->id;
                $role->role_id = 2;
                // $role->save();

                $mentors[] = $mentor;
            }
        }

        return $mentors;
    }

    public function import_mentor()
    {
        
    }

    public function recap_student($isNull = true)
    {
        $students = array();
        $alumni = Alumni::select('st_id')->get();
        $client = Client::whereHas('programs', function($query) {
            $query->where('prog_main', 'Admissions Mentoring')->where('stprog_status', 1);
        })->whereNotIn('st_id', $alumni)->when(!$isNull, function ($query) {
                $query->where(function($q1) {
                    $q1->where('st_firstname', '!=', '')->where('st_lastname', '!=', '')->where('st_mail', '!=', '');
                });
        })->distinct()->get();

        foreach ($client->unique('st_mail') as $client_data) {
            $find = Students::where('email', $client_data->st_mail)->count();
            if ($find == 0) { // if there are no data with client data email then save record
                $students[] = array(
                    'first_name' => $client_data->st_firstname,
                    'last_name' => $client_data->st_lastname,
                    'birthday' => $this->remove_invalid_date($client_data->st_dob),
                    'phone_number' => $client_data->st_phone,
                    'grade' => isset($client_data->school->sch_level) ? $this->remove_string_grade($client_data->school->sch_level) : null,
                    'email' => $this->remove_blank($client_data->st_mail),
                    'email_verified_at' => $client_data->st_mail != null ? Carbon::now() : null,
                    'address' => $this->remove_blank($client_data->st_address, 'text'),
                    'city' => $this->remove_blank($client_data->st_city),
                    'password' => $this->remove_blank($client_data->st_password),
                    'imported_from' => 'u5794939_allin_bd',
                    'imported_id' => $this->remove_blank($client_data->st_id, 'text'),
                    'status' => 1,
                    'is_verified' => $client_data->st_mail == '' ? 0 : 1,
                    'school_name' => isset($client_data->school->sch_name) ? ($client_data->school->sch_name == '-' ? null : $client_data->school->sch_name) : null
                );
            }
        }

        return $students;
    }

    public function import_student()
    {
        $students = $this->recap_student(false);

        DB::beginTransaction();
        try {
            Students::insert($students);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Import Data Student Issue : '.$e->getMessage());
            throw New Exception('Failed to import students data');
        }

        return 'Students data has been imported';
    }

    //** HELPER */

    public function map($value)
    {
        if ($value === "")
        {
            return null;
        }
        return $value;
        // if (is_array($value)) {
        //     return array_map("map", $value);
        // }
        // return $value === "" ? null : $value;
    }

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
        return empty($data) ? null : $data;
    }

    public function remove_string_grade($grade)
    {
        $output = preg_replace( '/[^0-9]/', '', $grade );
        return $output;
    }
}
