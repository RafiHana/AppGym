<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'secret',
        'events',
        'is_active'
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean'
    ];

    public function logs()
    {
        return $this->hasMany(WebhookLog::class);
    }
}