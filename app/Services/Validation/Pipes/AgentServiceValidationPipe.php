<?php

namespace App\Services\Varyza\Validations\Pipes;

use App\Services\Validation\ValidationContext;
use Closure;

class AgentServiceValidationPipe
{
    public function handle(ValidationContext $context, Closure $next)
    {
        // 1. Déjà validé par un agent ? On passe au niveau suivant.
        // (Supposons que vous ayez une relation ou un champ JSON pour stocker les validations)
        if ($context->attribution->hasValidation('agent_service')) {
            return $next($context);
        }

        // 2. Est-ce que l'utilisateur connecté est un agent qui est en train de valider ?
        if ($context->currentUser->hasRole('agent_service')) {
            $context->attribution->recordValidation('agent_service', $context->currentUser->id);
            // On continue le pipeline pour voir si la prochaine étape peut être déclenchée
            return $next($context); 
        }

        // 3. Sinon, on bloque le pipeline ici.
        $context->pendingStepMessage = "En attente de la validation par l'Agent de Service.";
        return $context;
    }
}