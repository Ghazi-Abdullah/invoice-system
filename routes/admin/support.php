<?php

use App\Http\Controllers\Admin\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:view_support_tickets')->group(function () {
    Route::get('/support/tickets', [SupportTicketController::class, 'index']);
    Route::get('/support/tickets/{ticket}', [SupportTicketController::class, 'show']);
});

Route::post('/support/tickets/{ticket}/replies', [SupportTicketController::class, 'reply'])
    ->middleware('permission:reply_support_ticket');

Route::patch('/support/tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])
    ->middleware('permission:edit_support_ticket_status');

Route::patch('/support/tickets/{ticket}/close', [SupportTicketController::class, 'close'])
    ->middleware('permission:edit_support_ticket_status');

Route::patch('/support/tickets/{ticket}/assign', [SupportTicketController::class, 'assign'])
    ->middleware('permission:assign_support_ticket'); // ⚠️ صلاحية جديدة — أضفها في seeder الصلاحيات

Route::delete('/support/tickets/{ticket}', [SupportTicketController::class, 'destroy'])
    ->middleware('permission:delete_support_ticket');
