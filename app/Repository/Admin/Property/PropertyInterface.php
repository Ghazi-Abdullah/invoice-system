<?php

namespace App\Repository\Admin\Property;

interface PropertyInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $property);
    public function destroy($property);
}