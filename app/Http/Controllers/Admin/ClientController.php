<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Client\ClientInterface;
use App\Http\Requests\Admin\Client\StoreClientRequest;
use App\Http\Requests\Admin\Client\UpdateClientRequest;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use ResponseTrait;

    protected $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_CLIENTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->client->index($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.clients_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_CLIENTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->client->show($id);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.client_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreClientRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_CLIENT)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->client->store($request);

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data'], 201);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateClientRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_CLIENT)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->client->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->client->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse($updateData['message'], $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_CLIENT)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->client->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->client->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse($deleteData['message'], $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }

    public function stats($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_CLIENTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->client->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $statsData = $this->client->getClientStats($data['data']);

        if ($statsData['status']) {
            return $this->successResponse($statsData['message'], $statsData['data']);
        }

        return $this->failureResponse($statsData['message'], $statsData['data']);
    }

    public function search(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_CLIENTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->client->searchClients($request);

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function invoices($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_CLIENTS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->client->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $invoicesData = $this->client->getClientInvoices($data['data']);

        if ($invoicesData['status']) {
            return $this->successResponse($invoicesData['message'], $invoicesData['data']);
        }

        return $this->failureResponse($invoicesData['message'], $invoicesData['data']);
    }
}
