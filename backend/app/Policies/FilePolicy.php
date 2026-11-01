<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FilePolicy
{
    public function upload(User $user): Response|bool
    {
        return $user->can('files.upload', 'sanctum');
    }

    public function view(User $user): Response|bool
    {
        return $user->can('files.view', 'sanctum');
    }

    public function viewAny(User $user): Response|bool
    {
        return $user->can('files.view', 'sanctum');
    }
}