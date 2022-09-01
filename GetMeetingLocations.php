<?php

// Script Filter GetMeetingLocations
public function GetMeetingLocations(Request $request, $filter)
{
    try {
        if ($filter == 'all')
            $meetingLocations = MasterMeetingLocations::orderBy('id', 'DESC');
        else if ($filter == 1)
            $meetingLocations = MasterMeetingLocations::where('is_active', true)
                ->orderBy('id', 'DESC');
        else if ($filter == 0)
            $meetingLocations = MasterMeetingLocations::where('is_active', false)
                ->orderBy('id', 'DESC');
        else return response()->json([
            'success' => false,
            'data' => [],
            'error' => 'Input error',
            'message' => 'Invalid path params'
        ], 400);

        // filter by name
        if ($request->has('name')) {
            $name = $request->input('name');
            $meetingLocations = $meetingLocations->where('name', 'ilike', '%' . $name . '%');
        }

        // filter by rent_status
        if ($request->has('rent_status')) {
            $rentStatus = $request->input('rent_status');
            $meetingLocations = $meetingLocations->where('rent_status', $rentStatus);
        }

        // filter by description
        if ($request->has('description')) {
            $description = $request->input('description');
            $meetingLocations = $meetingLocations->where('description', 'ilike', '%' . $description . '%');
        }

        // filter by location
        if ($request->has('location')) {
            $location = $request->input('location');
            $meetingLocations = $meetingLocations->where('location', 'ilike', '%' . $location . '%');
        }

        // filter by quota
        if ($request->has('quota')) {
            $quota = $request->input('quota');
            $meetingLocations = $meetingLocations->where('quota', 'ilike', '%' . $quota . '%');
        }

        // filter by is_active
        if ($request->has('is_active')) {
            $isActive = $request->boolean('is_active');
            $meetingLocations = $meetingLocations->where('is_active', $isActive);
        }

        // add is_already_used
        $meetingLocations = $meetingLocations->get();
        foreach ($meetingLocations as $location) {
            $count = Meetings::where('master_meeting_locations_id', $location->id)->count();
            $location->is_already_used = $count > 0 ? true : false;
        }

        // filter by is_already_used
        if ($request->has('is_already_used')) {
            $isAlreadyUsed = $request->boolean('is_already_used');
            // $meetingLocations = $meetingLocations->where('is_already_used', $isAlreadyUsed);

            foreach ($meetingLocations as $key => $location) {
                if ($location->is_already_used != $isAlreadyUsed) {
                    $meetingLocations->forget($key);
                }
            };
        }

        return response()->json([
            'success' => true,
            'data' => $meetingLocations,
            'error' => null,
            'message' => 'Success to get data meeting location!'
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
