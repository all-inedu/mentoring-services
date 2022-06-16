<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\ReminderNextGroupMeeting;
use App\Jobs\SendAnnouncementCancelGroupMeeting;
use App\Models\GroupMeeting;
use App\Models\GroupProject;
use App\Models\Participant;
use App\Models\StudentAttendances;
use App\Models\UserAttendances;
use App\Models\Students;
use App\Providers\RouteServiceProvider;
use App\Rules\CheckExistingEmailAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Validation\Rule;
use PHPUnit\TextUI\XmlConfiguration\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class GroupController extends Controller
{
    protected $student_id;
    protected $STUDENT_GROUP_PROJECT_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->user()->id;
        $this->STUDENT_GROUP_PROJECT_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_GROUP_PROJECT_VIEW_PER_PAGE;
    }

    //* group project main function start
    
    public function index($status)
    {
        $rules = [
            'status' => 'required|string|in:new,in-progress,completed'
        ];

        $validator = Validator::make(array('status' => $status), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        switch ($status) {
            case "new":
                $group_projects = GroupProject::whereHas('group_participant', function ($query) {
                        $query->where('student_id', $this->student_id)->where('participants.status', 0);
                    })->orderBy('created_at', 'desc')->paginate($this->STUDENT_GROUP_PROJECT_VIEW_PER_PAGE);

                break;

            case "in-progress":
                $group_projects = GroupProject::whereHas('group_participant', function ($query) {
                        $query->where('student_id', $this->student_id)->where('participants.status', 1);
                    })->where('status', 'in progress')->withCount('group_participant')->orderBy('created_at', 'desc')->paginate($this->STUDENT_GROUP_PROJECT_VIEW_PER_PAGE);
                break;

            case "completed":
                $group_projects = GroupProject::whereHas('group_participant', function ($query) {
                    $query->where('student_id', $this->student_id)->where('participants.status', 1);
                })->where('status', 'completed')->withCount('group_participant')->orderBy('created_at', 'desc')->paginate($this->STUDENT_GROUP_PROJECT_VIEW_PER_PAGE);

                break;
        }

        return response()->json(['success' => true, 'data' => $group_projects]);
    }

    public function find ($group_id)
    {
        if (!$group = GroupProject::without(['students'])->find($group_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the group']);
        }

        return response()->json(['success' => true, 'data' => array(
            'group_info' => $group->makeHidden(['students', 'group_participant', 'group_meeting']),
            'student_info' => Students::find($this->student_id),
            'group_member' => $group->group_participant()->select('students.id', 'students.first_name', 'students.last_name', 'contribution_role', 'contribution_description')->where('participants.status', '!=', 2)->orderBy('participants.created_at', 'asc')->get(),
            'group_meeting' => $group->group_meeting()->orderBy('group_meetings.created_at', 'desc')->get()->makeHidden(['student_attendances', 'user_attendances'])
        )]);
    }

    public function store(Request $request)
    {
        $rules = [
            'project_name'    => 'required|string|max:255',
            'project_type'    => 'required|string|max:255|in:group mentoring,profile building mentoring',
            'project_desc'    => 'required',
            'progress_status' => 'nullable|in:on track,behind,ahead',
            'status'          => 'required|in:in progress,completed',
            'owner_type'      => 'required|in:student,mentor'
        ];

        $input = $request->all();
        $input['project_type'] = strtolower($request->project_type);

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $group_projects = new GroupProject;
            $group_projects->student_id = $this->student_id;
            $group_projects->project_name = $request->project_name;
            $group_projects->project_type = $request->project_type;
            $group_projects->project_desc = $request->project_desc;
            $group_projects->status = $request->status;
            $group_projects->owner_type = $request->owner_type;
            $group_projects->save();

            // select all of mentor that handle this student 
            if ($student = Students::with('users:id')->where('id', $this->student_id)->first()) {
                $student_mentor = $student->users;
                foreach ($student_mentor as $mentor_detail) {
                    $data[] = array(
                        'group_id' => $group_projects->id,
                        'user_id' => $mentor_detail->id
                    );
                }
                
                $group_projects->assigned_mentor()->attach($data);
            }
            
            $group_projects->group_participant()->attach($this->student_id, [
                'status' => 1,
                'mail_sent_status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Group Project Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create group project. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Group Project has been made', 'data' => $group_projects]);
    }

    public function update($group_id, Request $request)
    {
        $rules = [
            'group_id'        => 'required|exists:group_projects,id',
            'project_name'    => 'required|string|max:255',
            'project_type'    => 'required|string|max:255|in:group mentoring,profile building mentoring',
            'project_desc'    => 'required',
            'progress_status' => 'nullable|in:on track,behind,ahead',
            'status'          => 'required|in:in progress,completed',
            'owner_type'      => 'required|in:student,mentor'
        ];

        $input = $request->all() + array('group_id' => $group_id);
        $input['project_type'] = strtolower($request->project_type);

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $group_project = GroupProject::find($group_id);
            //* validate only owner (student) of the group able to update 
            $owner = $group_project->owner_type == "student" ? $group_project->student_id : null; //* validate student (change null with user_id if the controller being called from admin)
            if ($owner != $this->student_id) {
                return response()->json(['success' => false, 'error' => 'You\'ve no permission to change the group info']);
            }
            
            $group_project->project_name = $request->project_name;
            $group_project->project_type = $request->project_type;
            $group_project->project_desc = $request->project_desc;
            $group_project->status = $request->status;
            $group_project->owner_type = $request->owner_type;
            $group_project->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Group Project Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update group project. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Group Project has been updated', 'data' => $group_project]);
    }

    //* group project main function end

    //* participant function start

    public function add_participant(Request $request)
    {
        $rules = [
            'group_id' => 'required|exists:group_projects,id',
            'participant.*' => ['required'/*, new CheckExistingEmailAddress*/]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $group = GroupProject::find($request->group_id);
        //* count jumlah participant untuk di looping
        $added_participant = count($request->participant); 

        DB::beginTransaction();
        try {
            $row_success = 0;
            $error_exists = ''; //* variable that hold the value of email when student email cannot be found
            $error_joined = ''; //* variable that hold the value of student name when student has already join the group
            for ($i = 0; $i < $added_participant ; $i++) {
                //* get student id by inputed email on participant variable
                if (!$student = Students::where('email', $request->participant[$i])->first()) {
                    $error_exists .= ($i > 0 ? ", " : "") . $request->participant[$i];
                    continue;
                }

                //* check if student has already joined into another group project
                if ($detail = $group->group_participant->where('id', $student->id)->first()) {
                    //* input the student that already joined into another group projects to array failed participant. Needed for show on error message
                    $error_joined .= ($i > 0 ? ", " : "") . $detail->first_name.' '.$detail->last_name;
                    continue;
                }

                $participant = new Participant;
                $participant->group_id = $request->group_id;
                $participant->student_id = $student->id;
                $participant->save();
                $row_success++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Participant Group Project Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => "Something went wrong. Please try again or contact our administrator"]);
        }

        // create variable response that hold various message of error
        if ($error_exists != '') {
            $response['error']['exists'] = "We couldn't find the following email : ". $error_exists;
        }

        if ($error_joined != '') {
            $response['error']['joined'] = "These students [" . $error_joined . "] has already joined the group";
        }

        // when error exist return error
        if (!empty($response['error'])) {
            return response()->json(['success' => false, 'error' => $response['error']]);
        }

        return response()->json([
            'success' => true, 
            'message' => $row_success != 0 ? $row_success : ''.' participant has been added to the Group Project : '.$group->project_name
                        // $failed_participant.' has already joined the Group Project',
        ]);
    }

    public function remove_participant($group_id, $student_id)
    {
    
        if (!$group_project = GroupProject::find($group_id)) {
            return response()->json(['success' => false, 'error' => 'Could not find group Id'], 400);
        }
        
        if (!$group_project->group_participant->where('id', $student_id)->first()) {
            return response()->json(['success' => false, 'error' => 'Could not find student on the group'], 400);
        }

        $creator_of_group = $group_project->student_id;
        $removed_participant = $student_id;
        //* validate if creator of the group same as student that will be removed
        //* cancel if match
        if ($creator_of_group == $removed_participant) {
            return response()->json(['success' => false, 'error' => 'You cannot removed the creator of the group'], 400);
        }

        DB::beginTransaction();
        try {
            $group_project->group_participant()->detach($student_id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Remove Participant From Group Project Issue : [ Group Id : '.$group_id.', Student Id : '.$student_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to remove participant from the group project. Please try again.']);
        }

        $student_detail = $group_project->group_participant->where('id', $student_id)->first();
        $student_name = $student_detail->first_name.' '.$student_detail->last_name;
        $student_name = ucwords(strtolower($student_name));

        return response()->json(['success' => true, 'message' => 'You\'ve removed '.$student_name.' from group']);
    }

    public function confirmation_invitee ($status = NULL, Request $request)
    {
        if ($status != NULL) {
            // when confirmation from dashboard
            $rules = [
                'group_id' => 'required|exists:group_projects,id',
                'student_id' => 'required|exists:students,id',
                'action' => 'required|in:accept,decline'
            ];
    
            $validator = Validator::make($request->all() + array('student_id' => $this->student_id), $rules);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'error' => $validator->errors()], 400);
            }
    
            $participant = Participant::where('group_id', $request->group_id)->where('student_id', $this->student_id)->first();
            $invitee_id = $participant->id;
        } else {
            // when confirmation from email
            $rules = [
                'key' => 'required|exists:participants,id',
                'action' => 'required|in:accept,decline'
            ];
    
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'error' => $validator->errors()], 400);
            }
    
            $invitee_id = $request->key;
        }
        switch ($request->input('action')) {
            case 'accept':
                $participant = Participant::find($invitee_id);
                $participant->status = 1;
                $participant->save();
                break;
            
            case 'decline':
                $participant = Participant::find($invitee_id);
                $participant->status = 2;
                $participant->save();
                break;
        }

        return response()->json(['success' => true, 'data' => $participant]);
    }

    public function update_participant_role_contribution ($group_id, $student_id, Request $request)
    {
        $rules = [
            'role' => 'required|max:255|regex:/^[A-Za-z]+$/',
            'description' => 'required|string|regex:/^[A-Za-z0-9., ]+$/'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $participant = Participant::where('group_id', $group_id)->where('student_id', $student_id)->first();
            $participant->contribution_role = $request->role;
            $participant->contribution_description = $request->description;
            $participant->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update role and contribution Issue : [ Group Id : '.$group_id.', Student Id : '.$student_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update role and contribution. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Your profile in the group project has been updated']);
    }
    //* participant function end

    //* meeting function start

    public function create_meeting (Request $request)
    {
        $meetings = GroupMeeting::where('mail_sent', 0)->get();
        foreach ($meetings as $meeting_detail) {
            $group_id = $meeting_detail->group_id;
            $all_email = array();

            $group_info = GroupProject::find($group_id);

            $participants = $meeting_detail->student_attendances;
            $mentors = $meeting_detail->user_attendances;

            //*email to participant
            foreach ($participants as $k => $v) {
                // array_push($all_email, $v->email);
                $encrypted_data = array(
                    'attend_id' => $v->pivot->id,
                    'group_meet_id' => $meeting_detail->id,
                );
                
                $meeting_detail['token'] = Crypt::encrypt($encrypted_data);
                $email = $v->email;
                $name = $v->first_name.' '.$v->last_name;
                $subject = "You've a new group meeting";

                Mail::send('templates.mail.next-group-meeting-announcement', ['name' => $name, 'group_info' => $group_info, 'meeting_detail' => $meeting_detail],
                    function($mail) use ($email, $name, $subject) {
                        $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@all-inedu.com");
                        $mail->to($email, $name);
                        $mail->subject($subject);
                    }); 

                if (count(Mail::failures()) > 0) { 
                    foreach (Mail::failures() as $email_address) {
                        Log::channel('group_meeting_reminder_log')->error("Sending reminder mail failures to ". $email_address);
                    }
                    continue;
                } 

                //* update sent mail to 1 if mail successfully delivered
                DB::beginTransaction();
                try {
                    
                    $attendances = StudentAttendances::find($v->pivot->id);
                    $attendances->mail_sent = 1;
                    $attendances->save();
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::channel('group_meeting_reminder_log')->error('Update Student Attendances Mail Sent Issue : [ Attend_id '.$v->pivot->id.' ] '.$e->getMessage());
                }
            }

            //* email to mentor
            foreach ($mentors as $k => $v) {
                // array_push($all_email, $v->email);
                $encrypted_data = array(
                    'attend_id' => $v->pivot->id,
                    'group_meet_id' => $meeting_detail->id,
                );

                $meeting_detail['token'] = Crypt::encrypt($encrypted_data);
                $email = $v->email;
                $name = $v->first_name.' '.$v->last_name;
                $subject = "Your student set a new group meeting";

                Mail::send('templates.mail.next-group-meeting-announcement', ['name' => $name, 'group_info' => $group_info, 'meeting_detail' => $meeting_detail],
                    function($mail) use ($email, $name, $subject) {
                        $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@all-inedu.com");
                        $mail->to($email, $name);
                        $mail->subject($subject);
                    }); 
                
                if (count(Mail::failures()) > 0) { 
                    foreach (Mail::failures() as $email_address) {
                        Log::channel('group_meeting_reminder_log')->error("Sending reminder mail failures to ". $email_address);
                    }
                    continue;
                } 

                //* update sent mail to 1 if mail successfully delivered
                DB::beginTransaction();
                try {
                    
                    $attendances = UserAttendances::find($v->pivot->id);
                    $attendances->mail_sent = 1;
                    $attendances->save();
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::channel('group_meeting_reminder_log')->error('Update Student Attendances Mail Sent Issue : [ Attend_id '.$v->pivot->id.' ] '.$e->getMessage());
                }
            }
        }return;
        $rules = [
            'group_id' => 'required|exists:group_projects,id',
            'meeting_date' => ['required', 'date_format:Y-m-d H:i', Rule::unique('group_meetings')->where(function ($query) use ($request) {
                return $query->where('group_id', $request->group_id);
            })],
            'meeting_link' => 'required|string|URL',
            'meeting_subject' => 'required|string|max:255'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $meeting = new GroupMeeting;
            $meeting->group_id = $request->group_id;
            $meeting->meeting_date = $request->meeting_date;
            $meeting->meeting_link = $request->meeting_link;
            $meeting->meeting_subject = $request->meeting_subject;
            $meeting->status = $request->status;
            $meeting->save();

            //* get group info
            $group = GroupProject::find($request->group_id);

            //* add participant to attendance
            $participant = $group->group_participant;
            foreach ($participant as $detail) {
                $meeting->student_attendances()->attach($meeting->id, [
                    'student_id' => $detail->id,
                    'created_at' => Carbon::now()
                ]);
            }

            //* add mentor to attendance
            $mentor = $group->assigned_mentor;
            foreach ($mentor as $detail) {
                $meeting->user_attendances()->attach($meeting->id, [
                    'user_id' => $detail->id,
                    'created_at' => Carbon::now()
                ]);
            }

            //* send email to mentor and the other member
            ReminderNextGroupMeeting::dispatch()->delay(now()->addSeconds(2));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Group Meeting Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create meeting. Please try again.']);
        }

        return response()->json([
            'success' => true, 'message' => 
            'Your next meeting is on '.date('d F Y', strtotime($request->meeting_date)).' at '.date('H:i', strtotime($request->meeting_date))
        ]);
    }

    public function attended ($encrypted_data)
    {
        $decrypted_data = Crypt::decrypt($encrypted_data);

        // validate
        if (!GroupMeeting::where('id', $decrypted_data['group_meet_id'])->where('status', 0)->whereHas('student_attendances', function($query) {
                $query->where('student_id', $this->student_id);
            })->first()) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the group meeting or you are not joined in the group project']);
        }

        // validate attendee
        DB::beginTransaction();
        try {
            $attendance = StudentAttendances::find($decrypted_data['attend_id']);
            $attendance->attend_status = 1;
            $attendance->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Attendance Status Issue : ['.json_encode($decrypted_data).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update attendance status. Please try again.']);
        }
        
        return response()->json(['success' => true, 'message' => 'Attendance status has updated']);
    }

    public function cancel_meeting ($meeting_id)
    {
        if (!$meeting_detail = GroupMeeting::find($meeting_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the group meeting']);
        }

        $mixed_data = array();
        $participants = $meeting_detail->student_attendances;
        $mentors = $meeting_detail->user_attendances;

        //*email to participant
        foreach ($participants as $k => $v) {
            array_push($mixed_data, array('email' => $v->email, 'name' => $v->first_name.' '.$v->last_name));
        }

        //* email to mentor
        foreach ($mentors as $k => $v) {
            array_push($mixed_data, array('email' => $v->email, 'name' => $v->first_name.' '.$v->last_name));
        }

        $data = array(
            'mixed_data' => $mixed_data,
            'meeting_detail' => $meeting_detail
        );

        //* send email notification that group meeting has been canceled
        SendAnnouncementCancelGroupMeeting::dispatch($data)->delay(now()->addSeconds(2));

        DB::beginTransaction();
        try {
            $meeting_detail->status = 2;
            $meeting_detail->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Cancel Group Meeting Issue : [ Meeting Id : '.$meeting_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to cancel group meeting. Please try again.']);
        }
        
        return response()->json(['success' => true, 'message' => 'Group meeting has cancelled']);
    }

    //* meeting function end
}
