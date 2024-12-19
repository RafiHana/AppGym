<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{   
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 
        'email', 
        'phone', 
        'password', 
        'rfid_card_number', 
        'membership_type', 
        'membership_start_date', 
        'membership_end_date', 
        'status', 
        'registered_by',
        'last_updated_by',
        'last_check_in',
        'total_check_ins'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'membership_start_date' => 'datetime',
        'membership_end_date' => 'datetime',
        'last_check_in' => 'datetime'
    ];

    // Relasi dengan transaksi keanggotaan
    public function transaksiMembers()
    {
        return $this->hasMany(TransaksiMember::class);
    }

    // Relasi dengan sesi gym
    public function sesiGyms()
    {
        return $this->hasMany(SesiGym::class);
    }

    // Relasi dengan user yang mendaftarkan
    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    //Pemberitahuan Untuk Update paket member
    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    // Relasi dengan paket member aktif
    public function activePaket()
    {
        return $this->hasOneThrough(
            PaketMember::class,
            TransaksiMember::class,
            'member_id',
            'id',
            'id',
            'paket_member_id'
        )->latest();
    }

    // Method untuk cek status keanggotaan
    public function isActive()
    {
        return $this->status === 'active' && now()->lt($this->membership_end_date);
    }

    // Validasi
    public static function validateMember($data, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:members,email' . ($id ? ",$id" : '')
            ],
            'phone' => 'required|string',
            'rfid_card_number' => 'required|unique:members,rfid_card_number' . ($id ? ",$id" : ''),
            'membership_type' => 'required|in:bronze,platinum,gold',
            'membership_start_date' => 'required|date',
            'membership_end_date' => 'required|date|after:membership_start_date',
            'status' => 'required|in:active,inactive,expired'
        ];

        return Validator::make($data, $rules);
    }

    // Tambahan method untuk cek apakah member bisa check-in
    public function canCheckIn()
    {
        return $this->isActive() && 
               (!$this->last_check_in || 
                $this->last_check_in->diffInHours(now()) >= 24);
    }

    // Tambahan scope untuk member aktif
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('membership_end_date', '>', now());
    }

    // Tambahan scope untuk member yang akan expired dalam x hari
    public function scopeExpiringIn($query, $days)
    {
        $date = now()->addDays($days);
        return $query->where('membership_end_date', '<=', $date)
                    ->where('membership_end_date', '>', now());
    }
}