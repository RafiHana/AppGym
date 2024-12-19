<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id',
        'event',
        'payload',
        'response',
        'status',
        'error',
        'sent_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime'
    ];

    public function endpoint()
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
