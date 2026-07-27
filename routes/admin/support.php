<?php

use App\Http\Controllers\Admin\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/support/tickets', [SupportTicketController::class, 'index']);
Route::get('/support/tickets/{id}', [SupportTicketController::class, 'show']);
Route::post('/support/tickets/{id}/replies', [SupportTicketController::class, 'reply']);
Route::patch('/support/tickets/{id}/close', [SupportTicketController::class, 'close']);
Route::delete('/support/tickets/{id}', [SupportTicketController::class, 'destroy']);
