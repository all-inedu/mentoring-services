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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use PHPUnit\TextUI\XmlConfiguration\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\MailLogController;
use App\Models\MailLog;
use Illuminate\Support\Facades\Redirect;

class GroupController extends Controller
{
    protected $student_id;
    protected $user_id;
    protected $STUDENT_GROUP_PROJECT_VIEW_PER_PAGE;
    protected $MENTOR_GROUP_PROJECT_VIEW_PER_PAGE;
    protected $ONGOING_PROJECT_DETAIL_HYPERLINK;
    protected $TO_MENTORS_GROUP_PROJECT_CREATED;
    protected $NOTIFICATION_HANDLER;

    public function __construct()
    {
        $this->student_id = Auth::guard('student-api')->check() ? Auth::guard('student-api')->user()->id : NULL;
        $this->user_id = Auth::guard('api')->check() ? Auth::guard('api')->user()->id : NULL;
        $this->STUDENT_GROUP_PROJECT_VIEW_PER_PAGE = RouteServiceProvider::STUDENT_GROUP_PROJECT_VIEW_PER_PAGE;
        $this->MENTOR_GROUP_PROJECT_VIEW_PER_PAGE = RouteServiceProvider::MENTOR_GROUP_PROJECT_VIEW_PER_PAGE;
        $this->ONGOING_PROJECT_DETAIL_HYPERLINK = RouteServiceProvider::ONGOING_PROJECT_DETAIL_HYPERLINK;
        $this->TO_MENTORS_GROUP_PROJECT_CREATED = RouteServiceProvider::TO_MENTORS_GROUP_PROJECT_CREATED;
        $this->NOTIFICATION_HANDLER = RouteServiceProvider::NOTIFICATION_HANDLER;
    }

    //* group project main function start
    
    public function index($status, $student_id = NULL)
    {
        $id = $student_id != NULL ? $student_id : $this->student_id;
        $view_per_page = $student_id != NULL ? $this->MENTOR_GROUP_PROJECT_VIEW_PER_PAGE : $this->STUDENT_GROUP_PROJECT_VIEW_PER_PAGE;

        $rules = [
            'status' => 'required|string|in:new,in-progress,completed'
        ];

        $validator = Validator::make(array('status' => $status), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        switch ($status) {
            case "new":
                $group_projects = GroupProject::whereHas('group_participant', function ($query) use ($id){
                        $query->where('student_id', $id)->where('participants.status', 0);
                    })->orderBy('created_at', 'desc')->paginate($view_per_page);

                break;

            case "in-progress":
                $group_projects = GroupProject::whereHas('group_participant', function ($query) use ($id) {
                        $query->where('student_id', $id)->where('participants.status', 1);
                    })->where('status', 'in progress')->withCount([
                        'group_participant' => function (Builder $query) {
                            $query->where('participants.status', '!=', 2);
                        }
                    ])->orderBy('created_at', 'desc')->paginate($view_per_page);
                break;

            case "completed":
                $group_projects = GroupProject::whereHas('group_participant', function ($query) use ($id) {
                    $query->where('student_id', $id)->where('participants.status', 1);
                })->where('status', 'completed')->withCount([
                    'group_participant' => function (Builder $query) {
                        $query->where('participants.status', '!=', 2);
                    }
                ])->orderBy('created_at', 'desc')->paginate($view_per_page);

                break;
        }

        return response()->json(['success' => true, 'data' => $group_projects]);
    }

    public function find ($person, $group_id, $student_id_from_url = NULL)
    {
        $rules = [
            'person'    => 'required|in:mentor,student',
            // 'student_id' => 'nullable|required_if:person,mentor|exists:students,id'
            // 'student_id' => 'nullable|required_if:person,student|exists:students,id'
        ];

        $validator = Validator::make(['person' => $person, 'student_id' => $student_id_from_url], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $student_id = ($person == "mentor") ? $student_id_from_url : $this->student_id;
        if (!$group = GroupProject::when($person == "student", function($query) use ($student_id) {
            $query->whereHas('group_participant', function($query1) use ($student_id) {
                $query1->where('participants.student_id', $student_id)->where(function($query2) {
                    $query2->where('participants.status', 0)->orWhere('participants.status', 1);
                });
            });
        })->find($group_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the group']);
        }
        $owner_id = $group->student_id;

        $group_member = $group->group_participant()->select('students.id', 'students.first_name', 'students.last_name', 'participants.status', 'contribution_role', 'contribution_description')->where('participants.status', '!=', 2)->orderBy('participants.created_at', 'asc')->get();

        foreach ($group_member as $member) {
            if ($member->id == $owner_id) {
                $member['owner'] = 'yes';
            } else {
                $member['owner'] = 'no';
            }
        }

        $student_info = $group->group_participant()->select('students.id', 'students.first_name', 'students.last_name', 'contribution_role', 'contribution_description')->where('participants.student_id', $this->student_id)->first();

        $student_info['owner'] = ($student_id == $owner_id) ? "yes" : "no";

        return response()->json(['success' => true, 'data' => array(
            'group_info' => $group->makeHidden(['students', 'group_participant', 'group_meeting']),
            'student_info' => $student_info,
            'group_member' => $group_member,
            'group_meeting' => $group->group_meeting()->orderBy('group_meetings.status', 'asc')->orderBy('group_meetings.created_at', 'asc')->get()->makeHidden(['student_attendances', 'user_attendances'])
        )]);
    }

    public function store(Request $request)
    {
        $rules = [
            'project_name'    => 'required|string|max:255',
            'project_type'    => 'nullable|string|max:255|in:group mentoring,profile building mentoring',
            'project_desc'    => 'required',
            'progress_status' => 'nullable|in:on track,behind,ahead',
            'status'          => 'required|in:in progress,completed',
            'owner_type'      => 'required|in:student',
            'picture'         => 'nullable|mimes:jpg,png|max:2048'
        ];

        $input = $request->all();
        $input['project_type'] = strtolower($request->project_type);

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if (!$student = Students::with('users:id,email,first_name,last_name')->where('id', $this->student_id)->first()) {
            return response()->json(['success' => false, 'error' => 'You have no mentor yet. Call the administrator to use this feature']);
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

            $directory_name = $group_projects->id;
            $encrypted_directory_name = Crypt::encrypt($directory_name);

            if ($request->hasFile('picture')) {
                $old_image_path = $group_projects->picture;
                if ($old_image_path != NULL) {
                    $file_path = substr($old_image_path, 7);
                    // check the old profile picture
                    // if exist do delete;
                    
                    $isExists = File::exists(public_path($file_path));
                    // dd($isExists);
                    if ($isExists) {
                        File::delete(public_path($file_path));
                    } else {
                        throw new Exception("Cannot find the file or the file does not exists");
                    }
                }

                $med_file_name = date('Ymd_His').'_group-thumbnail';
                $med_file_format = $request->file('picture')->getClientOriginalExtension();
                $med_file_path = $request->file('picture')->storeAs($directory_name, $med_file_name.'.'.$med_file_format, ['disk' => 'group_project_files']);

                $group_projects->picture = 'public/media/group/'.$med_file_path;
                $group_projects->save();
            }
            

            // select all of mentor that handle this student 
            
            $student_mentor = $student->users;
            if (count($student_mentor) == 0) {
                throw new Exception("There are no mentor that handle student : ".$student->first_name.' '.$student->last_name);
            }
            
            foreach ($student_mentor as $mentor_detail) {
                $data[] = array(
                    'group_id' => $group_projects->id,
                    'user_id' => $mentor_detail->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                );

                $mail_data['mentor_name'] = $mentor_detail->first_name.' '.$mentor_detail->last_name;
                $mail_data['hyperlink'] = $this->ONGOING_PROJECT_DETAIL_HYPERLINK;
                $mail_data['group_detail'] = array(
                    'project_id' => $group_projects->id,
                    'project_name' => $group_projects->project_name,
                    'project_type' => $group_projects->project_type,
                    'project_desc' => $group_projects->project_desc,
                    'project_owner' => $group_projects->student_id != NULL ? $group_projects->students->first_name.' '.$group_projects->students->first_name : $group_projects->users->first_name.' '.$group_projects->users->last_name,
                ); 

                // mail to mentor
                try {

                    Mail::send('templates.mail.to-mentors.invitation-group-project', ['group_info' => $mail_data], function($mail) use ($mentor_detail)  {
                        $mail->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                        $mail->to($mentor_detail->email, $mentor_detail->first_name.' '.$mentor_detail->last_name);
                        $mail->subject($this->TO_MENTORS_GROUP_PROJECT_CREATED);
                    });
                } catch (Exception $e) {
                    // save to log mail admin
                    // save only if failure to sent
                    $log = array(
                        'sender'    => 'student',
                        'recipient' => $mentor_detail->email ? $mentor_detail->email : "Cannot fetch mentor email",
                        'subject'   => 'Sending notification to mentor that mentee has created group project',
                        'message'   => json_encode($mail_data),
                        'date_sent' => Carbon::now(),
                        'status'    => "not delivered",
                        'error_message' => $e->getMessage()
                    );
                    $save_log = new MailLogController;
                    $save_log->saveLogMail($log);
                    DB::commit();
                    throw new Exception($e);
                }

            }
            
            
            $group_projects->assigned_mentor()->attach($data);
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
            // 'project_type'    => 'nullable|string|max:255|in:group mentoring,profile building mentoring',
            'project_desc'    => 'required',
            'progress_status' => 'nullable|in:on track,behind,ahead',
            'status'          => 'required|in:in progress,completed',
            'owner_type'      => 'nullable|in:student,mentor',
            'picture'         => 'nullable|mimes:jpg,png|max:2048'
        ];

        $input = $request->all() + array('group_id' => $group_id);
        $input['project_type'] = strtolower($request->project_type);

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $group_project = GroupProject::where('student_id', $this->student_id)->where('id', $group_id)->first();
            //* validate only owner (student) of the group able to update 
            $owner = $group_project->owner_type == "student" ? $group_project->student_id : null; //* validate student (change null with user_id if the controller being called from admin)
            if ($owner != $this->student_id) {
                return response()->json(['success' => false, 'error' => 'You\'ve no permission to change the group info']);
            }
            
            $group_project->project_name = $request->project_name;
            $group_project->project_type = $request->project_type;
            $group_project->project_desc = $request->project_desc;
            if ($request->status != NULL) { //! will be deleted soon
                $group_project->status = $request->status;
            }
            if ($request->owner_type != NULL) { //! will be deleted soon
                $group_project->owner_type = $request->owner_type;
            }
            
            if ($request->hasFile('picture')) {
                $old_image_path = $group_project->picture;
                if ($old_image_path != NULL) {
                    $file_path = substr($old_image_path, 7);
                    // check the old profile picture
                    // if exist do delete;
                    
                    $isExists = File::exists(public_path($file_path));
                    // dd($isExists);
                    if ($isExists) {
                        File::delete(public_path($file_path));
                    } else {
                        throw new Exception("Cannot find the file or the file does not exists");
                    }
                }

                $med_file_name = date('Ymd_His').'_group-thumbnail';
                $med_file_format = $request->file('picture')->getClientOriginalExtension();
                $med_file_path = $request->file('picture')->storeAs($group_id, $med_file_name.'.'.$med_file_format, ['disk' => 'group_project_files']);
    
                $group_project->picture = 'public/media/group/'.$med_file_path;
            }
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
                    if (($detail->pivot->status == 0) || ($detail->pivot->status == 1)) {
                        //* input the student that already joined into another group projects to array failed participant. Needed for show on error message
                        $error_joined .= ($i > 0 ? ", " : "") . $detail->first_name.' '.$detail->last_name;
                        continue;
                    }
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

        $attendee = $row_success != 0 ? $row_success : 0;

        return response()->json([
            'success' => true, 
            'message' => $attendee.' participant has been added to the Group Project : '.$group->project_name
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
            $from_mail = false;
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
    
            $participant = Participant::where('group_id', $request->group_id)->where('student_id', $this->student_id)->where('status', 0)->first();
            $invitee_id = $participant->id;
        } else {
            $from_mail = true;
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

        // get group detail
        $group = GroupProject::withAndWhereHas('group_participant', function ($query) use ($invitee_id) {
            $query->where('participants.id', $invitee_id);
        })->first();

        return response()->json($group);

        switch ($request->input('action')) {
            case 'accept':
                $message = "You've accepted to join project : ".$group->project_name;
                $participant = Participant::find($invitee_id);
                $status = 1;
                break;
            
            case 'decline':
                $message = "You've declined to join the project : ".$group->project_name;
                $participant = Participant::find($invitee_id);
                $status = 2;
                break;
        }

        DB::beginTransaction();
        try {
            $participant->status = $status;
            $participant->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Confirmation Invitee Issue : [ Invitee Id : '.$invitee_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to confirm invitee. Please try again.']);
        }

        $response = $from_mail ? Redirect::to($this->NOTIFICATION_HANDLER.urlencode($message)) : response()->json(['success' => true, 'message' => $message, 'data' => $participant]);
        return $response;
    }

    public function update_participant_role_contribution ($group_id, Request $request)
    {
        $rules = [
            'role' => 'required|max:255',
            'description' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            
            $participant = Participant::where('group_id', $group_id)->where('student_id', $this->student_id)->first();
            $participant->contribution_role = $request->role;
            $participant->contribution_description = $request->description;
            $participant->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update role and contribution Issue : [ Group Id : '.$group_id.', Student Id : '.$this->student_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update role and contribution. Please try again.']);
        }

        $student = Students::select('id', 'first_name', 'last_name')->where('id', $this->student_id)->first();

        return response()->json(['success' => true, 'message' => 'Your profile in the group project has been updated', 'data' => array(
            'student_info' => $student,
            'participant' => $participant
        )]);
    }
    //* participant function end

    //* meeting function start

    public function create_meeting (Request $request)
    {
        
        $rules = [
            'group_id' => ['required', Rule::exists('group_projects', 'id')->where(function($query) {
                $query->where('student_id', $this->student_id);
            })],
            'start_date' => 'required|date_format:Y-m-d H:i|after_or_equal:'.date('Y-m-d'),
            'end_date' => 'required|date_format:Y-m-d H:i|after:start_date',
            // 'meeting_date' => ['required', 'date_format:Y-m-d H:i', 'after_or_equal:'.date('Y-m-d', strtotime("+1 days")), Rule::unique('group_meetings')->where(function ($query) use ($request) {
            //     return $query->where('group_id', $request->group_id)->where('status', 0);
            // })],
            'meeting_link' => 'required|string|URL',
            'meeting_subject' => 'required|string|max:255'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $from = $request->start_date;
        $to = $request->end_date;

        if ($group_meeting = GroupMeeting::where(function($query) use ($from, $to) {
            $query->whereBetween('start_meeting_date', [$from, $to])
            ->orWhereBetween('end_meeting_date', [$from, $to]);
        })->whereHas('student_attendances', function($query) {
            // adakah student yang memiliki jadwal group meeting di range tgl tersebut
            $query->where('student_id', $this->student_id)->where('attend_status', 1);
        })->where('group_meetings.status', 0)->count() > 0) {
            return response()->json([
                'success' => false, 
                'error' => 'You already have group meeting around '.date('d M Y H:i', strtotime($from)).'. Please make sure you don\'t have any group meeting schedule schedule before creating a new one.',
            ]);
        }

        DB::beginTransaction();
        try {

            $meeting = new GroupMeeting;
            $meeting->group_id = $request->group_id;
            $meeting->start_meeting_date = $from;
            $meeting->end_meeting_date = $to;
            $meeting->meeting_link = $request->meeting_link;
            $meeting->meeting_subject = $request->meeting_subject;
            $meeting->status = $request->status;
            $meeting->save();

            //* get group info
            $group = GroupProject::find($request->group_id);

            //* add participant to attendance
            $participant = $group->group_participant()->where('participants.status', 1)->get();

            foreach ($participant as $detail) {
                
                $meeting->student_attendances()->attach($meeting->id, [
                    'student_id' => $detail->id,
                    'attend_status' => $detail->id == $this->student_id ? 1 : 0,
                    'mail_sent' => $detail->id == $this->student_id ? 1 : 0,
                    'created_at' => Carbon::now(),
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
            // ReminderNextGroupMeeting::dispatch()->delay(now()->addSeconds(2));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Group Meeting Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create meeting. Please try again.']);
        }

        return response()->json([
            'success' => true, 'message' => 
            'Your next meeting is on '.date('d F Y', strtotime($from)).' at '.date('H:i', strtotime($from))
        ]);
    }

    public function attended ($person, $encrypted_data)
    {
        $rules = [ 
            'person' => 'in:mentor,student'
        ];

        $validator = Validator::make(['person' => $person], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $decrypted_data = Crypt::decrypt($encrypted_data);

        // validate
        switch ($person) {
            case "mentor":
                if (!GroupMeeting::where('group_meetings.id', $decrypted_data['group_meet_id'])->whereHas('user_attendances', function($query) use ($decrypted_data) {
                    $query->where('user_attendances.id', $decrypted_data['attend_id']);
                })->where('status', 0)->first()) {
                    return response()->json(['success' => false, 'error' => 'Couldn\'t find the group meeting or you are not joined in the group project']);
                }
                break;

            case "student":
                if (!GroupMeeting::where('group_meetings.id', $decrypted_data['group_meet_id'])->whereHas('student_attendances', function($query) use ($decrypted_data) {
                    $query->where('student_attendances.id', $decrypted_data['attend_id']);
                })->where('status', 0)->first()) {
                    return response()->json(['success' => false, 'error' => 'Couldn\'t find the group meeting or you are not joined in the group project']);
                }
                break;
        }
        

        // validate attendee
        DB::beginTransaction();
        try {
            $attendance = $person == "mentor" ? UserAttendances::find($decrypted_data['attend_id']) : StudentAttendances::find($decrypted_data['attend_id']);
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

    public function cancel_meeting ($person, $meeting_id)
    {
        if (!$meeting_detail = GroupMeeting::find($meeting_id)) {
            return response()->json(['success' => false, 'error' => 'Couldn\'t find the group meeting']);
        }

        if (!$meeting_detail->group_project()->when($person == "mentor", function($query) {
                $query->where(function($query1){
                    $query1->where('user_id', $this->user_id)->orWhereHas('assigned_mentor', function ($query2) {
                        $query2->where('users.id', $this->user_id);
                    });
                });
            })->when($person == "student", function($query) {
                $query->where('student_id', $this->student_id);
            })->first()
        ) {
            return response()->json(['success' => false, 'error' => 'You don\'t have permission to cancel this meeting']);
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
        SendAnnouncementCancelGroupMeeting::dispatch($data)->delay(now()->addSeconds(60));

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
        
        return response()->json(['success' => true, 'message' => 'Group meeting has canceled']);
    }

    //* meeting function end
}
