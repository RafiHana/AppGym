<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $fillable = [
        'name', 
        'key', 
        'is_active', 
        'usage_count', 
        'last_used_at', 
        'created_by'
    ];

    protected $dates = ['last_used_at'];

    // Generate API Key
    public static function generateKey()
    {
        return 'GYM_' . strtoupper(bin2hex(random_bytes(16)));
    }
}
