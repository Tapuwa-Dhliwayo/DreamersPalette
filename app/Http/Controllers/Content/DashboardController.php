<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\DashboardRequest;
use App\Http\Requests\Studio\WritingGoalRequest;
use App\Models\Manuscript;
use App\Services\StudioDashboard;

class DashboardController extends Controller
{
    public function show(DashboardRequest $request, StudioDashboard $dashboard)
    {
        return response()->json($dashboard->forAuthor($request->user(), $request->validated()));
    }

    public function goal(WritingGoalRequest $request, Manuscript $manuscript)
    {
        // Setting a goal is not writing, so it must not reorder recent work.
        $manuscript->timestamps = false;
        $manuscript->forceFill($request->validated())->save();

        return response()->json(['weekly_word_goal' => $manuscript->weekly_word_goal]);
    }
}
