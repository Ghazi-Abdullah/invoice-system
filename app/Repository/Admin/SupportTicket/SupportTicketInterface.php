<?php

namespace App\Repository\Admin\SupportTicket;

interface SupportTicketInterface
{
    public function index($request);
    public function show($id);
    public function track($request);
    public function store($request);
    public function reply($request, $ticket);
    public function updateStatus($request, $ticket);
    public function assign($request, $ticket);
    public function destroy($ticket);
}
