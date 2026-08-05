<?php

namespace App\Repository\Admin\RecurringInvoice;

interface RecurringInvoiceInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $template);
    public function destroy($template);
    public function generateNow($template);
    public function cancel($template);
}