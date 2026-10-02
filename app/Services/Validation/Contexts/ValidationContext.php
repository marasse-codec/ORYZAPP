<?php

namespace App\Services\Validation;

use App\Models\Attribution;
use App\Models\User;

class ValidationContext
{
    public ?string $pendingStepMessage = null;
    public bool $isFullyValidated = false;

    public function __construct(
        public Attribution $attribution,
        public User $currentUser
    ) {}
}