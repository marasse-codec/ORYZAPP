<?php

namespace App\Services\Validation;

use Closure;

interface ValidationPipeInterface
{
    public function handle(ValidationContext $context, Closure $next): ValidationContext;
}