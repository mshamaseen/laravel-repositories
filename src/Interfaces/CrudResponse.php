<?php

namespace Shamaseen\Repository\Interfaces;

interface CrudInterface
{
    public function index();
    public function show($entity);
    public function create();
    public function store();
    public function delete();
}
