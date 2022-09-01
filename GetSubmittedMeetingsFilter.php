<?php

// Script Filter GetSubmittedMeetingsFilter
public function GetSubmittedMeetingsFilter(Request $request, $identityNumber)
{
    try {
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
            )
            ->join('examiner_flow_reviews AS EFR', 'EFR.meetings_id', '=', 'M.id')
            ->join('examiner_review_histories AS ERH', 'ERH.examiner_flow_reviews_id', '=', 'EFR.id')
            ->where('EFR.examiner_identity_number', $identityNumber)
            ->whereIn('ERH.approve_type_id', [3, 4])
            ->whereIn('M.notulen_status_id', [2, 3]);

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
            $meetings = $meetings->where('EFR.examiner_identity_number', 'ilike', '%' . $request->input('examiner_identity_number') . '%');
        }

        // filter by notulen_status_id
        if ($request->has('notulen_status_id')) {
            $meetings = $meetings->where('M.notulen_status_id', '=', $request->input('notulen_status_id'));
        }

        $meetings = $meetings->orderBy('M.id', 'desc')->distinct()->get();
        foreach ($meetings as $meeting) {
            // create new object
            $meeting->noteTakers;
            $meeting->notulenStatus;
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

            // set note takers identity
            $meeting->noteTakers;

            // set examiner
            $meeting->examiner;

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
