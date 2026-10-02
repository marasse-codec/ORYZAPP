<?php

use App\Services\Validation\ValidationContext;

class FinalizeAttributionPipe
{
    public function handle(ValidationContext $context, Closure $next)
    {
        // Si le flux arrive ici, tous les niveaux précédents ont validé.
        $context->isFullyValidated = true;
        
        // On change le statut final de la ressource ADERIZ
        $context->attribution->update(['status' => 'TOTALEMENT_VALIDE']);
        
        return $next($context);
    }
}