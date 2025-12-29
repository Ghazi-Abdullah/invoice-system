<?php

namespace App\Repository\User\Client;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Constants\Constants;

class ClientRepository implements ClientInterface
{
    public function index()
    {
        $user = Auth::user();

        // المستخدم العادي يرى فقط عملاءه
        return Client::where('user_id', $user->id)
            ->with(['invoices' => function($query) {
                $query->select('id', 'client_id', 'invoice_number', 'total_amount', 'status', 'issue_date');
            }])
            ->latest()
            ->get();
    }

    public function show(Client $client)
    {
        $user = Auth::user();

        // التأكد من أن العميل يخص المستخدم
        if ($client->user_id !== $user->id) {
            throw new \Exception(__('messages.no_permission'));
        }

        return $client->load(['invoices.items']);
    }

    public function store($request)
    {
        $user = Auth::user();

        $data = $request->validated();
        $data['user_id'] = $user->id;

        return Client::create($data);
    }

    public function update($request, Client $client)
    {
        $user = Auth::user();

        // التأكد من أن العميل يخص المستخدم
        if ($client->user_id !== $user->id) {
            throw new \Exception(__('messages.no_permission'));
        }

        $client->update($request->validated());
        return $client->load('invoices');
    }

    public function destroy(Client $client)
    {
        $user = Auth::user();

        // التأكد من أن العميل يخص المستخدم
        if ($client->user_id !== $user->id) {
            throw new \Exception(__('messages.no_permission'));
        }

        // التحقق مما إذا كان العميل لديه فواتير
        if ($client->invoices()->count() > 0) {
            throw new \Exception(__('messages.client_has_invoices'));
        }

        $client->delete();
        return true;
    }

    public function search($searchTerm)
    {
        $user = Auth::user();

        return Client::where('user_id', $user->id)
            ->where(function($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%")
                      ->orWhere('phone', 'like', "%{$searchTerm}%")
                      ->orWhere('company_name', 'like', "%{$searchTerm}%");
            })
            ->with('invoices')
            ->get();
    }
}
