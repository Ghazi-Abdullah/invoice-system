<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Tenant\TenantInterface;
use App\Http\Requests\Admin\Tenant\StoreTenantRequest;
use App\Http\Requests\Admin\Tenant\UpdateTenantRequest;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use ResponseTrait;

    protected $tenant;

    public function __construct(TenantInterface $tenant)
    {
        $this->tenant = $tenant;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_TENANTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->index($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.tenants_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_TENANTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->show($id);

        if ($data['status']) {
            return $this->successResponse(__('messages.tenant_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreTenantRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_TENANT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->store($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.tenant_created'), $data['data'], Constants::RESPONSE_CREATED);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateTenantRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_TENANT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->tenant->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(__('messages.tenant_updated'), $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_TENANT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->tenant->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->tenant->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(__('messages.tenant_deleted'), $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }
}
