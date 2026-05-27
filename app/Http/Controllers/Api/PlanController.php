<?php

/**
 * API controller for plans.
 * Exposes endpoints to manage and view plans.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(10);

        return PlanResource::collection($plans);
    }

    public function show(Plan $plan)
    {
        abort_unless($plan->is_active, 404);

        return new PlanResource($plan);
    }
}
