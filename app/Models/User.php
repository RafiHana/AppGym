<?php

namespace App\Models;

use App\Models\SesiGym;
use App\Models\TransaksiMember;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email', 
        'password', 
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Validasi saat membuat/update user
    public static function validateUser($data, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:users,email' . ($id ? ",$id" : '')
            ],
            'password' => $id ? 'sometimes|min:8' : 'required|min:8',
            'role' => 'in:superadmin,admin'
        ];

        return Validator::make($data, $rules);
    }

    // Relasi dengan transaksi
    public function transaksiMembers()  // Diubah dari TransaksiMember
    {
        return $this->hasMany(TransaksiMember::class, 'processed_by');
    }

    public function sesiGyms()  // Diubah dari SesiGym
    {
        return $this->hasMany(SesiGym::class, 'verified_by');
    }

    // Tambahan relasi dengan Member (untuk tracking siapa yang mendaftarkan member)
    public function registeredMembers()
    {
        return $this->hasMany(Member::class, 'registered_by');
    }


}
