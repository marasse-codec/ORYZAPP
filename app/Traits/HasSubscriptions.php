<?php

namespace App\Traits;

use App\Models\ResourceSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSubscriptions
{
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(ResourceSubscription::class, 'subscribable');
    }

    public function isSubscribedBy(?User $user): bool
    {
        if (!$user) return false;
        return $this->subscriptions()->where('user_id', $user->id)->exists();
    }

    public function toggleSubscription(User $user, string $eventType = 'all'): bool
    {
        $subscription = $this->subscriptions()
            ->where('user_id', $user->id)
            ->where('event_type', $eventType)
            ->first();

        if ($subscription) {
            $subscription->delete();
            return false; // Désabonné
        }

        $this->subscriptions()->create([
            'user_id' => $user->id,
            'event_type' => $eventType,
        ]);
        return true; // Abonné
    }
}