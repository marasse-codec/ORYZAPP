<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    protected $table = 'notifications_rules';

    protected $fillable = [
        'name',
        'resource_type',
        'event',
        'field',
        'operator',
        'value',
        'title_template',
        'message_template',
        'is_active',
        'recipient_type',
        'recipient_targets',
        'channels'

    ];

    protected $casts = [
        'is_active' => 'boolean',
        'recipient_targets' => 'array',
        'channels' => 'array'
    ];
}