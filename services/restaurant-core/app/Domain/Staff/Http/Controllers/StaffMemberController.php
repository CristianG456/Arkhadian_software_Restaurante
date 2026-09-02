<?php

namespace App\Domain\Staff\Http\Controllers;

use App\Domain\Orders\Http\Resources\OrderResources;
use App\Domain\Staff\Http\Requests\StaffMemberRequest;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Staff\Services\StaffMemberService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class StaffMemberController extends Controller
{
    public function __construct(private readonly StaffMemberService $service) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(['staff' => $this->service->list()->map(fn ($staff) => OrderResources::staff($staff))->all()]);
    }

    public function store(StaffMemberRequest $request): JsonResponse
    {
        return ApiResponse::success(['staffMember' => OrderResources::staff($this->service->create($request->validated()))], 201);
    }

    public function update(StaffMemberRequest $request, StaffMember $staffMember): JsonResponse
    {
        return ApiResponse::success(['staffMember' => OrderResources::staff($this->service->update($staffMember, $request->validated()))]);
    }
}
