<?php

namespace App\Repository\Admin\Floor;

interface FloorInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $floor);
    public function destroy($floor);
}