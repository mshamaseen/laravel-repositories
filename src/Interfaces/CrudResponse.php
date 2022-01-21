<?php

namespace Shamaseen\Repository\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface CrudResponse
{
    public function index($paginate);
    public function show(Model $entity);
    public function create();
    public function store(Model $entity);
    public function edit(Model $entity);
    public function update(int $updatedCount);
    public function destroy(int $destroyedCount);
}
