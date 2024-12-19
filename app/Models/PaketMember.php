<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class PaketMember extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'type', 
        'price', 
        'duration_months', 
        'description', 
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'benefits' => 'array'
    ];

    // Relasi dengan transaksi
    public function transaksiMembers()
    {
        return $this->hasMany(TransaksiMember::class);
    }

    // Scope untuk paket aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Tambahan relasi dengan Member melalui TransaksiMember
    public function members()
    {
        return $this->belongsToMany(Member::class, 'transaksi_members', 'paket_member_id', 'member_id');
    }

    // Tambahan scope untuk paket berdasarkan tipe
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Mendapatkan paket berdasarkan tipe
    public static function getPackageByType($type)
    {
        return self::where('type', $type)
                   ->where('is_active', true)
                   ->first();
    }

    // Validasi paket
    public static function validatePackage($data, $id = null)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'unique:paket_members,name' . ($id ? ",$id" : '')
            ],
            'type' => 'required|in:bronze,platinum,gold',
            'duration_months' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'benefits' => 'nullable|array'
        ];

        return Validator::make($data, $rules);
    }
}