<?php

namespace Database\Factories\Core\Tenant\Models;

use Core\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition()
    {
        return ['name' => 'Test Workspace'];
    }
}
