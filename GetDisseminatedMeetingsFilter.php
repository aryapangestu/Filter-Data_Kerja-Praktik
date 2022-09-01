<?php

// Script Filter GetDisseminatedMeetingsFilter
public function GetDisseminatedMeetingsFilter(Request $request, $identityNumber)
{
    try {
        // check params
        if ($identityNumber == 'admin') {
            $meetings = Meetings::from('meetings AS M')
                ->select(
                    'M.id',
                    'M.meeting_name',
                    'M.notulen_status_id',
                    'M.meeting_notes',
                    'M.start_date',
                    'M.end_date',
                    'M.start_time',
                    'M.end_time',
                    'M.created_by',
                    'M.master_meeting_types_id',
                    'M.master_meeting_locations_id',
                    'M.meeting_leader_identity_number',
                    'M.meeting_leader_full_name',
                    'M.meeting_leader_position_name',
                    'M.other_meeting_type',
                    'M.other_meeting_location'
                )->where('notulen_status_id', 4);

            // filter by meeting_name
            if ($request->has('meeting_name')) {
                $meetings = $meetings->Where('M.meeting_name', 'ilike', '%' . $request->input('meeting_name') . '%');
            }

            // filter by start_date
            if ($request->has('start_date')) {
                $meetings = $meetings->where('M.start_date', '=', $request->input('start_date'));
            }

            // filter by location
            if ($request->has('location')) {
                $meetings = $meetings->leftJoin('master_meeting_locations AS MML', 'MML.id', '=', 'M.master_meeting_locations_id')
                    ->where(function ($q) use ($request) {
                        $q->where('MML.name', 'ilike', '%' . $request->input('location') . '%')
                            ->orWhere('M.other_meeting_location', 'ilike', '%' . $request->input('location') . '%');
                    });
            }

            // filter by meeting_types_name
            if ($request->has('meeting_types_name')) {
                $meetings = $meetings->leftJoin('master_meeting_types AS MMT', 'MMT.id', '=', 'M.master_meeting_types_id')
                    ->where(function ($q) use ($request) {
                        $q->where('MMT.name', 'ilike', '%' . $request->input('meeting_types_name') . '%')
                            ->orWhere('M.other_meeting_type', 'ilike', '%' . $request->input('meeting_types_name') . '%');
                    });
            }

            // filter by meeting_leader_identity_number
            if ($request->has('meeting_leader_identity_number')) {
                $meetings = $meetings->where('M.meeting_leader_identity_number', 'ilike', '%' . $request->input('meeting_leader_identity_number') . '%');
            }

            // filter by note_taker_identity_number
            if ($request->has('note_taker_identity_number')) {
                $meetings = $meetings->leftJoin('note_takers AS NT', 'M.id', '=', 'NT.meetings_id')
                    ->where('NT.note_taker_identity_number', 'ilike', '%' . $request->input('note_taker_identity_number') . '%');
            }

            // filter by examiner_identity_number
            if ($request->has('examiner_identity_number')) {
                $meetings = $meetings->join('examiner_flow_reviews AS EFR', 'EFR.meetings_id', '=', 'M.id')
                    ->where('EFR.examiner_identity_number', 'ilike', '%' . $request->input('examiner_identity_number') . '%');
            }

            $meetings = $meetings->orderBy('M.id', 'desc')->distinct()->get();
        } else {
            // keterlibatan sebagai pemimpin rapat
            $byMeetingLeader = Meetings::where('meeting_leader_identity_number', $identityNumber)
                ->where('notulen_status_id', 4);

            // keterlibatan sebagai notulis
            $byNoteTaker = Meetings::select('meetings.*')
                ->leftJoin('note_takers', 'meetings.id', '=', 'note_takers.meetings_id')
                ->where('note_takers.note_taker_identity_number', $identityNumber)
                ->where('meetings.notulen_status_id', 4);

            // keterlibatan sebagai pemeriksa
            $byExaminer = Meetings::select('meetings.*')
                ->leftJoin('examiner_flow_reviews', 'meetings.id', '=', 'examiner_flow_reviews.meetings_id')
                ->where('examiner_flow_reviews.examiner_identity_number', $identityNumber)
                ->where('meetings.notulen_status_id', 4);

            // keterlibatan sebagai partisipan internal
            $byInternalParticipant = Meetings::select('meetings.*')
                ->leftJoin('internal_participants', 'meetings.id', '=', 'internal_participants.meetings_id')
                ->where('participant_identity_number', $identityNumber)
                ->where('meetings.notulen_status_id', 4);

            // keterlibatan sebagai PIC
            $byPic = Meetings::select('meetings.*')
                ->leftJoin('meeting_agendas', 'meeting_agendas.meetings_id', '=', 'meetings.id')
                ->leftJoin('agenda_discussions', 'agenda_discussions.meeting_agendas_id', '=', 'meeting_agendas.id')
                ->leftJoin('follow_up_discussions', 'follow_up_discussions.agenda_discussions_id', '=', 'agenda_discussions.id')
                ->leftJoin('follow_up_outcomes', 'follow_up_outcomes.follow_up_discussions_id', '=', 'follow_up_discussions.id')
                ->leftJoin('pic', 'pic.follow_up_outcomes_id', '=', 'follow_up_outcomes.id')
                ->where('pic.pic_identity_number', $identityNumber)
                ->where('meetings.notulen_status_id', 4);

            $meetings = DB::connection('pgsql-emom')
                ->table(DB::raw("(" .
                    $byMeetingLeader->union($byNoteTaker)
                    ->union($byPic)
                    ->union($byExaminer)
                    ->union($byInternalParticipant)
                    ->toSql()
                    . ") A"))
                ->mergeBindings($byMeetingLeader->getQuery());
            // ->orderBy('id', 'desc')
            // ->get();

            //filter by meeting_name
            if ($request->has('meeting_name')) {
                $meetings = $meetings->whereRaw("A.meeting_name ILIKE '%" . $request->input('meeting_name') . "%'");
            }

            // filter by start_date
            if ($request->has('start_date')) {
                $meetings = $meetings->whereRaw("A.start_date = '%" . $request->input('start_date') . "%'");
            }

            // filter by location
            if ($request->has('location')) {
                $meetings = $meetings->Join(DB::Raw("master_meeting_locations AS MML"), DB::raw("MML.id"), DB::raw("A.master_meeting_locations_id"))
                    ->whereRaw("MML.name ilike '%" . $request->input('location') . "%'")
                    ->orWhereRaw("A.other_meeting_location ilike '%" . $request->input('location') . "%'");
            }

            // filter by meeting_types_name
            if ($request->has('meeting_types_name')) {
                $meetings = $meetings->leftJoin(DB::Raw("master_meeting_types AS MMT"), DB::raw("MMT.id"), DB::raw("A.master_meeting_types_id"))
                    ->whereRaw("MMT.name ilike '%" . $request->input('meeting_types_name') . "%'")
                    ->orWhereRaw("A.other_meeting_type ilike '%" . $request->input('meeting_types_name') . "%'");
            }

            // filter by meeting_leader_identity_number
            if ($request->has('meeting_leader_identity_number')) {
                $meetings = $meetings->whereRaw("A.meeting_leader_identity_number ilike '%" . $request->input('meeting_leader_identity_number') . "%'");
            }

            // filter by note_taker_identity_number
            if ($request->has('note_taker_identity_number')) {
                $meetings = $meetings->leftJoin(DB::Raw("note_takers AS NT"), DB::raw("A.id"), DB::raw("NT.meetings_id"))
                    ->whereRaw("NT.note_taker_identity_number ilike '%" . $request->input('note_taker_identity_number') . "%'");
            }

            // filter by examiner_identity_number
            if ($request->has('examiner_identity_number')) {
                $meetings = $meetings->leftJoin(DB::Raw("examiner_flow_reviews AS EFR"), DB::raw("EFR.meetings_id"), DB::raw("A.id"))
                    ->whereRaw("EFR.examiner_identity_number ilike '%" . $request->input('examiner_identity_number') . "%'");
            }

            $meetings = $meetings->selectRaw("DISTINCT A.*")->orderByRaw("A.id DESC")->get();
        }

        foreach ($meetings as $meeting) {
            // create new object
            $meeting->meeting_type = new stdClass();
            $meeting->meeting_location = new stdClass();

            // set meeting type
            if ($meeting->master_meeting_types_id == null) {
                $meeting->meeting_type->id = null;
                $meeting->meeting_type->name = $meeting->other_meeting_type;
                $meeting->meeting_type->description = null;
                $meeting->meeting_type->is_active = null;
                $meeting->meeting_type->source = 'other';
            } else {
                $meetingType = MasterMeetingTypes::find($meeting->master_meeting_types_id);
                $meeting->meeting_type->id = $meetingType->id;
                $meeting->meeting_type->name = $meetingType->name;
                $meeting->meeting_type->description = $meetingType->description;
                $meeting->meeting_type->is_active = $meetingType->is_active;
                $meeting->meeting_type->source = 'master';
            }

            // set meeting location
            if ($meeting->master_meeting_locations_id == null) {
                $meeting->meeting_location->id = null;
                $meeting->meeting_location->name = $meeting->other_meeting_location;
                $meeting->meeting_location->description = null;
                $meeting->meeting_location->rent_status = null;
                $meeting->meeting_location->is_active = null;
                $meeting->meeting_location->quota = null;
                $meeting->meeting_location->source = 'other';
            } else {
                $meetingLocation = MasterMeetingLocations::find($meeting->master_meeting_locations_id);
                $meeting->meeting_location->id = $meetingLocation->id;
                $meeting->meeting_location->name = $meetingLocation->name;
                $meeting->meeting_location->description = $meetingLocation->description;
                $meeting->meeting_location->rent_status = $meetingLocation->rent_status;
                $meeting->meeting_location->is_active = $meetingLocation->is_active;
                $meeting->meeting_location->quota = $meetingLocation->quota;
                $meeting->meeting_location->source = 'master';
            }

            if ($identityNumber == 'admin') {
                // set utility
                $meeting->noteTakers;
                $meeting->notulenStatus;
                $meeting->examiner;
            } else {
                // set utility
                $meeting->note_takers = new stdClass();
                $meeting->notulen_status = new stdClass();
                $meeting->examiner = new stdClass();

                // set note_takers
                $meeting->note_takers = NoteTakers::where('meetings_id', $meeting->id)->get();

                // set notulen_status
                $meeting->notulen_status = NotulenStatus::find($meeting->notulen_status_id);

                // set examiner
                $meeting->examiner = ExaminerFlowReviews::where('meetings_id', $meeting->id)->get();
            }

            // unset object
            unset($meeting->master_meeting_types_id);
            unset($meeting->other_meeting_type);
            unset($meeting->master_meeting_locations_id);
            unset($meeting->other_meeting_location);
        }

        return response()->json([
            'success' => true,
            'data' => $meetings,
            'error' => null,
            'message' => 'Success to get data draft meetings!'
        ], 200);
    } catch (Exception $err) {
        return response()->json([
            'success' => false,
            'data' => [],
            'error' => $err->getTrace(),
            'message' => $err->getMessage()
        ], 500);
    }
}
