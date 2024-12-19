<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesiGym extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 'check_in_time', 'check_out_time', 'total_duration', 'status', 'verified_by'
    ];

    // Relasi dengan Member
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'total_duration' => 'integer'
    ];

    // Method untuk menghitung durasi
    public function calculateDuration()
    {
        if ($this->check_in_time && $this->check_out_time) {
            return $this->check_in_time->diffInMinutes($this->check_out_time);
        }
        return 0;
    }

    // Scope untuk sesi aktif
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope untuk sesi hari ini
    public function scopeToday($query)
    {
        return $query->whereDate('check_in_time', now());
    }
}
