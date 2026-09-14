<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MembershipPlanServiceInterface;
use App\DTOs\MembershipPlans\CreateMembershipPlanData;
use App\DTOs\MembershipPlans\UpdateMembershipPlanData;
use App\Http\Requests\MembershipPlans\StoreMembershipPlanRequest;
use App\Http\Requests\MembershipPlans\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MembershipPlanController extends Controller
{
    public function __construct(
        private readonly MembershipPlanServiceInterface $membershipPlanService
    ) {}

    public function index(): Response
    {
        return Inertia::render('MembershipPlans/Index', [
            'plans' => $this->membershipPlanService->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MembershipPlans/Create');
    }

    public function store(
        StoreMembershipPlanRequest $request
    ): RedirectResponse {
        $data = CreateMembershipPlanData::fromRequest($request);

        $this->membershipPlanService->create($data);

        return redirect()
            ->route('membership-plans.index')
            ->with('success', 'Membership plan created successfully.');
    }

    public function show(
        MembershipPlan $membershipPlan
    ): Response {
        return Inertia::render('MembershipPlans/Show', [
            'plan' => $membershipPlan,
        ]);
    }

    public function edit(
        MembershipPlan $membershipPlan
    ): Response {
        return Inertia::render('MembershipPlans/Edit', [
            'plan' => $membershipPlan,
        ]);
    }

    public function update(
        UpdateMembershipPlanRequest $request,
        MembershipPlan $membershipPlan
    ): RedirectResponse {
        $data = UpdateMembershipPlanData::fromRequest($request);

        $this->membershipPlanService->update(
            $membershipPlan,
            UpdateMembershipPlanData::fromRequest($request),
        );

        return redirect()
            ->route('membership-plans.index', $membershipPlan)
            ->with('success', 'Membership plan updated successfully.');
    }

    public function destroy(
        MembershipPlan $membershipPlan
    ): RedirectResponse {
        $this->membershipPlanService->deletePlan(
            $membershipPlan
        );

        return redirect()
            ->route('membership-plans.index')
            ->with('success', 'Membership plan deleted successfully.');
    }
}
