<?php

// Script Filter GetMeetingTypes
public function GetMeetingTypes(Request $request, $filter)
{
    try {
        if ($filter == 'all')
            $meetingType = MasterMeetingTypes::orderBy('id', 'DESC');
        else if ($filter == 1)
            $meetingType = MasterMeetingTypes::where('is_active', true)
                ->orderBy('id', 'DESC');
        else if ($filter == 0)
            $meetingType = MasterMeetingTypes::where('is_active', false)
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
            $meetingType = $meetingType->where('name', 'ilike', '%' . $name . '%');
        }

        // filter by description
        if ($request->has('description')) {
            $description = $request->input('description');
            $meetingType = $meetingType->where('description', 'ilike', '%' . $description . '%');
        }

        // filter by is_active
        if ($request->has('is_active')) {
            $isActive = $request->boolean('is_active');
            $meetingType = $meetingType->where('is_active', $isActive);
        }

        // add is_already_used
        $meetingType = $meetingType->get();
        foreach ($meetingType as $type) {
            $count = Meetings::where('master_meeting_types_id', $type->id)->count();
            $type->is_already_used = $count > 0 ? true : false;
        }

        // filter by is_already_used
        if ($request->has('is_already_used')) {
            $isAlreadyUsed = $request->boolean('is_already_used');
            // $meetingType = $meetingType->where('is_already_used', $isAlreadyUsed);

            foreach ($meetingType as $key => $type) {
                if ($type->is_already_used != $isAlreadyUsed) {
                    $meetingType->forget($key);
                }
            };
        }

        return response()->json([
            'success' => true,
            'data' => $meetingType,
            'error' => null,
            'message' => 'Success to get data meeting type!'
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
