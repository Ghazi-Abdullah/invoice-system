<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Floor\FloorInterface;
use App\Http\Requests\Admin\Floor\StoreFloorRequest;
use App\Http\Requests\Admin\Floor\UpdateFloorRequest;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    use ResponseTrait;

    protected $floor;

    public function __construct(FloorInterface $floor)
    {
        $this->floor = $floor;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_FLOORS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->index($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.floors_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_FLOORS)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->show($id);

        if ($data['status']) {
            return $this->successResponse(__('messages.floor_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreFloorRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_FLOOR)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->store($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.floor_created'), $data['data'], Constants::RESPONSE_CREATED);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateFloorRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_FLOOR)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->floor->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(__('messages.floor_updated'), $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_FLOOR)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->floor->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->floor->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(__('messages.floor_deleted'), $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }
}
