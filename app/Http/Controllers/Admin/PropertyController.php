<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Property\PropertyInterface;
use App\Http\Requests\Admin\Property\StorePropertyRequest;
use App\Http\Requests\Admin\Property\UpdatePropertyRequest;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    use ResponseTrait;

    protected $property;

    public function __construct(PropertyInterface $property)
    {
        $this->property = $property;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_PROPERTIES)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->index($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.properties_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_PROPERTIES)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->show($id);

        if ($data['status']) {
            return $this->successResponse(__('messages.property_fetched'), $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StorePropertyRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_PROPERTY)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->store($request);

        if ($data['status']) {
            return $this->successResponse(__('messages.property_created'), $data['data'], Constants::RESPONSE_CREATED);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdatePropertyRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_PROPERTY)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->property->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(__('messages.property_updated'), $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_PROPERTY)) {
            return $this->failureResponse(__('messages.no_permission'), null, Constants::RESPONSE_FORBIDDEN);
        }

        $data = $this->property->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->property->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(__('messages.property_deleted'), $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }
}
