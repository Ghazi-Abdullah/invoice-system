<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Unit\UnitInterface;
use App\Http\Requests\Admin\Unit\StoreUnitRequest;
use App\Http\Requests\Admin\Unit\UpdateUnitRequest;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use ResponseTrait;

    protected $unit;

    public function __construct(UnitInterface $unit)
    {
        $this->unit = $unit;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_UNITS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->index($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.units_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_UNITS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->show($id);

        if ($data['status']) {
            return $this->successResponse(__('messages.unit_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreUnitRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_UNIT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->store($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.unit_created'), $data['data'], Constants::RESPONSE_CREATED);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateUnitRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_UNIT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->unit->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(__('messages.unit_updated'), $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_UNIT)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->unit->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->unit->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(__('messages.unit_deleted'), $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }
}