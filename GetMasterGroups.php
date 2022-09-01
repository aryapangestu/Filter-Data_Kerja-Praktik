<?php

// Script Filter GetMasterGroups
public function GetMasterGroups(Request $request, $filter)
{
    try {
        if ($filter == 'all')
            $masterGroups = MasterGroups::orderBy('id', 'DESC');
        else if ($filter == 1)
            $masterGroups = MasterGroups::where('is_active', true)
                ->orderBy('id', 'DESC');
        else if ($filter == 0)
            $masterGroups = MasterGroups::where('is_active', false)
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
            $masterGroups = $masterGroups->where('name', 'ilike', '%' . $name . '%');
        }

        // filter by description
        if ($request->has('description')) {
            $description = $request->input('description');
            $masterGroups = $masterGroups->where('description', 'ilike', '%' . $description . '%');
        }

        // filter by is_active
        if ($request->has('is_active')) {
            $isActive = $request->boolean('is_active');
            $masterGroups = $masterGroups->where('is_active', $isActive);
        }

        // get data
        $masterGroups = $masterGroups->get();
        return response()->json([
            'success' => true,
            'data' => $masterGroups,
            'error' => null,
            'message' => 'Success to get data master groups!'
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
