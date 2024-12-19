<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\SesiGymController;
use App\Http\Controllers\Api\TransaksiMemberController;
use App\Http\Controllers\Api\WebhookController;

Route::prefix('v1')->group(function () {
    
    // Route untuk RFID Device with API Key (DONE)
    // http://127.0.0.1:8000/api/v1/device/check-out
    // http://127.0.0.1:8000/api/v1/device/check-in
    Route::middleware(['api.key'])->group(function () {
        Route::post('/device/check-in', [SesiGymController::class, 'checkIn']);
        Route::post('/device/check-out', [SesiGymController::class, 'checkOut']);
    }); //  nomor 1 end point di fitur sesicontroller 
    
    // Route untuk Transaksi Membership (renew & packages DONE)
    // Untuk tets di Postman atau rester 
    // http://127.0.0.1:8000/api/v1/membership/packages     Mengambil semua paket membership
    // http://127.0.0.1:8000/api/v1/membership/renew         Mengambil semua paket membership
    Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->prefix('membership')->group(function () {
        Route::post('/renew', [TransaksiMemberController::class, 'renewMembership']);
        Route::get('/packages', [TransaksiMemberController::class, 'getMembershipPackages']); 
    }); // nomor 2 end oint di fitur transaksimembercontroller
    
    // Route untuk Gym Sessions ( occupany & history Done )
    // http://127.0.0.1:8000/api/v1/gym-sessions/history     Mengambil riwayat sesi gym sebelumnya
    // http://127.0.0.1:8000/api/v1/gym-sessions/occupancy    Mengambil kapasitas gym saat sedang di gunakan member
    Route::middleware(['auth:sanctum'])->prefix('gym-sessions')->group(function () {
        Route::get('/history', [SesiGymController::class, 'sessionHistory']);
        Route::get('/occupancy', [SesiGymController::class, 'getCurrentOccupancy']);
    }); // nomor 3 end oint di fitur sesigymcontroller
    
    // Route Autentikasi
    // http://127.0.0.1:8000/api/v1/auth/login Untuk superadmin,admin dan member
    // http://127.0.0.1:8000/api/v1/auth/logout Untuk superadmin,admin dan member
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Route untuk Superadmin dan Admin (Pendaftaran Admin dan Member) DONE
    // http://127.0.0.1:8000/api/v1/auth/register/admin Mendaftar Admin
    // http://127.0.0.1:8000/api/v1/auth/register/member Mendaftar Member
    Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->prefix('auth')->group(function () {
        Route::post('/register/admin', [AuthController::class, 'registerAdminUser']);
        Route::post('/register/member', [MemberController::class, 'register']);
    });

    // Route untuk Superadmin (Get admin DONE)
    // http://127.0.0.1:8000/api/v1/superadmin/admins
    // http://127.0.0.1:8000/api/v1/superadmin/admins/1
    // http://127.0.0.1:8000/api/v1/superadmin/admins/1
    Route::middleware(['auth:sanctum', 'role:superadmin'])->prefix('superadmin')->group(function () {
        Route::prefix('admins')->group(function () {
            Route::get('/', [AuthController::class, 'getAllAdmins']);
            Route::put('/{id}', [AuthController::class, 'updateAdmin']);
            Route::delete('/{id}', [AuthController::class, 'deleteAdmin']);
        });
    });

    // Route untuk Admin dan Superadmin (get, get{1}, DONE)
    // get http://127.0.0.1:8000/api/v1/admin/members       Mengambil semua member
    // get http://127.0.0.1:8000/api/v1/admin/members/1     Mengambil member tertentu
    // put http://127.0.0.1:8000/api/v1/admin/members/1     Mengubah member tertentu
    // delete http://127.0.0.1:8000/api/v1/admin/members/1  Menghapus member tertentu
    Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->prefix('admin')->group(function () {
        Route::get('/members', [MemberController::class, 'getAllMembers']);
        Route::get('/members/{id}', [MemberController::class, 'getMember']);
        Route::put('/members/{id}', [MemberController::class, 'updateMember']);
        Route::delete('/members/{id}', [MemberController::class, 'deleteMember']);

        // http://127.0.0.1:8000/api/v1/admin/reports/membership Melihat report paket terbanyak
        // http://127.0.0.1:8000/api/v1/admin/reports/gym-usage Melihat report gym terbanyak
        // http://127.0.0.1:8000/api/v1/admin/reports/revenue  Melihat total Penghasilan setiap hari dari gym
        Route::prefix('reports')->group(function () {
            Route::get('/membership', [MemberController::class, 'membershipReport']); 
            Route::get('/gym-usage', [SesiGymController::class, 'gymUsageReport']);
            Route::get('/revenue', [TransaksiMemberController::class, 'revenueReport']);
        });
    });

    // Route untuk Member (get profile DONE)
    // http://127.0.0.1:8000/api/v1/members/profile          Mengambil profile member
    // http://127.0.0.1:8000/api/v1/members/profile          Mengubah profile member
    // http://127.0.0.1:8000/api/v1/members/activity-report/1 Mengambil report aktivitas member
    Route::middleware(['auth:sanctum'])->prefix('members')->group(function () {
        Route::get('/profile', [MemberController::class, 'getProfile']); 
        Route::put('/profile', [MemberController::class, 'updateProfile']);
        Route::get('/activity-report/{memberId}', [MemberController::class, 'memberActivityReport']);
    });    
    // Fallback route ( Jika mendapatkan endpoint yang tidak ada )
    Route::fallback(function () {
        return response()->json([
            'message' => 'Endpoint tidak ditemukan',
            'status' => 404
        ], 404);
    });
});
