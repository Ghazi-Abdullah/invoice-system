<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        try {
            $clients = Client::where('user_id', $request->user()->id)
                ->latest()
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $clients
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch clients: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:clients,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:50',
            ]);

            $client = Client::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'company_name' => $request->company_name,
                'tax_number' => $request->tax_number,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Client $client)
    {
        try {
            $client->load(['invoices']);

            return response()->json([
                'success' => true,
                'data' => $client
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch client: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Client $client)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:clients,email,' . $client->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:50',
            ]);

            $client->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'Client updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Client $client)
    {
        try {
            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client: ' . $e->getMessage()
            ], 500);
        }
    }
}
