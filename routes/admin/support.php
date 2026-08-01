<?php

use App\Http\Controllers\Admin\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:view_support_tickets')->group(function () {
    Route::get('/support/tickets', [SupportTicketController::class, 'index']);
    Route::get('/support/tickets/{id}', [SupportTicketController::class, 'show']);
});

Route::post('/support/tickets/{id}/replies', [SupportTicketController::class, 'reply'])
    ->middleware('permission:reply_support_ticket');

Route::patch('/support/tickets/{id}/status', [SupportTicketController::class, 'updateStatus'])
    ->middleware('permission:edit_support_ticket_status');

Route::patch('/support/tickets/{id}/close', [SupportTicketController::class, 'close'])
    ->middleware('permission:edit_support_ticket_status');

Route::delete('/support/tickets/{id}', [SupportTicketController::class, 'destroy'])
    ->middleware('permission:delete_support_ticket');

Route::post('/support/tickets', [SupportTicketController::class, 'store'])
    ->middleware('permission:create_support_ticket');
