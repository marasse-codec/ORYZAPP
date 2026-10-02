<?php

use App\Services\Validation\ValidationContext;

class AgentComptableValidationPipe
{
    public function handle(ValidationContext $context, Closure $next)
    {
        if ($context->attribution->hasValidation('directeur')) {
            return $next($context);
        }

        if ($context->currentUser->hasRole('directeur')) {
            $context->attribution->recordValidation('directeur', $context->currentUser->id);
            return $next($context);
        }

        $context->pendingStepMessage = "En attente de la validation par la Direction.";
        return $context;
    }
}