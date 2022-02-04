<?php

namespace Shamaseen\Repository\Interfaces;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

interface CrudResponse
{
    public function index(Paginator|Collection $paginate): Response|View;

    public function show(Model $entity): Response|View;

    public function create(): Response|View;

    public function store(Model $entity): Response|View;

    public function edit(Model $entity): Response|View;

    public function update(int|bool|Model $updatedCount): Response|View;

    public function destroy(int|bool $destroyedCount): Response|View;
}
