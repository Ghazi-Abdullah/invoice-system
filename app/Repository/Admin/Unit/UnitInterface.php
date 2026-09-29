<?php

namespace App\Repository\Admin\Unit;

interface UnitInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $unit);
    public function destroy($unit);
}
