<?php

namespace App\Repository\Admin\Tenant;

interface TenantInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $tenant);
    public function destroy($tenant);
}
