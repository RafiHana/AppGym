<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 
        'paket_member_id', 
        'amount', 
        'transaction_date', 
        'payment_method',
        'payment_status',
        'processed_by',
        'notes'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2'
    ];

    // Relasi dengan Member
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // Relasi dengan PaketMember
    public function paketMember()
    {
        return $this->belongsTo(PaketMember::class);
    }

    // Relasi dengan User yang memproses
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scope untuk transaksi sukses
    public function scopeSuccess($query)
    {
        return $query->where('payment_status', 'success');
    }

    // Tambahan scope untuk filter berdasarkan metode pembayaran
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // Tambahan scope untuk transaksi dalam periode tertentu
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Method untuk mengupdate membership member
    public function updateMembership()
    {
        if ($this->payment_status === 'success') {
            $paket = $this->paketMember;
            $member = $this->member;

            $startDate = now();
            $endDate = now()->addMonths($paket->duration_months);

            $member->update([
                'membership_type' => $paket->type,
                'membership_start_date' => $startDate,
                'membership_end_date' => $endDate,
                'status' => 'active'
            ]);
        }
    }
}