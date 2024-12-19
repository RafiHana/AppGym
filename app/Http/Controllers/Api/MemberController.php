<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Models\SesiGym;
use App\Models\TransaksiMember;
use App\Models\User;
use App\Models\PaketMember;
use App\Services\WebhookService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class MemberController extends Controller
{
 public function register(Request $request) // Fungsi untuk mendaftarkan member
    {
        // Validasi input dasar
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255', // Validasi nama
            'email' => 'required|email|unique:members,email', // Validasi email unik
            'phone' => 'required|string', // Validasi nomor telepon
            'membership_type' => 'required|in:bronze,platinum,gold', // Validasi tipe keanggotaan
            'payment_method' => 'required|in:cash,transfer,credit_card' // Validasi metode pembayaran
        ]);

        if ($validator->fails()) { // Jika validasi gagal
            return response()->json([
                'message' => 'Validasi Gagal', // Mengembalikan pesan error
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Mulai transaksi database
            return DB::transaction(function () use ($request) { // Memulai transaksi database
                // Ambil user yang sedang login
                $currentUser = Auth::user(); // Ambil user yang sedang login

                // Jika tidak ada user yang login, kembalikan error
                if (!$currentUser) {
                    throw new \Exception('Anda harus login untuk mendaftarkan member'); // Mengembalikan pesan error
                }

                // Ambil paket member sesuai tipe
                $paketMember = PaketMember::getPackageByType($request->membership_type); // Ambil paket member sesuai tipe
                
                if (!$paketMember) {
                    throw new \Exception('Paket member tidak ditemukan'); // Mengembalikan pesan error
                }

                // Generate RFID (contoh sederhana)
                $rfidCardNumber = 'RFID-' . strtoupper(uniqid()); // Generate RFID untuk di berikan kepada member

                // Buat member baru
                $member = Member::create([ //   Membuat entri member baru
                    'name' => $request->name, // Menyimpan nama
                    'email' => $request->email, // Menyimpan email
                    'phone' => $request->phone, // Menyimpan nomor telepon
                    'password' => Hash::make(substr($request->phone, -6)), // Contoh: password default 6 digit terakhir no telp
                    'rfid_card_number' => $rfidCardNumber, // Menyimpan nomor kartu RFID
                    'membership_type' => $request->membership_type, // Menyimpan tipe keanggotaan
                    'membership_start_date' => now(), // Tanggal mulai keanggotaan
                    'membership_end_date' => now()->addMonths($paketMember->duration_months), // Tangal akhir keanggotaan
                    'status' => 'active', // Status keanggotaan saat ini
                    'total_check_ins' => 0, // Jumlah check-in saat ini
                    'registered_by' => $currentUser->id, // Tambahkan admin yang mendaftarkan
                    'last_updated_by' => $currentUser->id //    Tambahkan admin yang memproses
                ]);

                // Buat transaksi member
                $transaksi = TransaksiMember::create([
                    'member_id' => $member->id, // Menyimpan ID member
                    'paket_member_id' => $paketMember->id, // Menyimpan ID paket member
                    'amount' => $paketMember->price, // Menyimpan harga paket member
                    'transaction_date' => now(), // Tanggal transaksi
                    'payment_method' => $request->payment_method, //    Metode pembayaran
                    'payment_status' => 'success', // Asumsi pembayaran langsung sukses
                    'processed_by' => $currentUser->id, // Tambahkan admin yang memproses
                    'notes' => 'Pendaftaran member baru' // Catatan tambahan
                ]);

                return response()->json([
                    'message' => 'Pendaftaran Berhasil', // Mengembalikan pesan sukses
                    'member' => $member,
                    'transaksi' => $transaksi
                ], 201);
            });
        } catch (\Exception $e) {
            // Tangani error
            return response()->json([
                'message' => 'Pendaftaran Member Gagal', // Mengembalikan pesan error
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function memberActivityReport($memberId) // Fungsi untuk menampilkan laporan aktivitas anggota
    {
        $member = Member::findOrFail($memberId); // Mencari member berdasarkan ID
        
        $sessions = SesiGym::where('member_id', $memberId) // Menyaring sesi berdasarkan ID anggota
            ->with('verifiedBy') // Mengambil relasi verifiedBy
            ->whereNotNull('check_out_time') // Menyaring sesi yang sudah check-out
            ->orderBy('check_in_time', 'desc') // Mengurutkan sesi berdasarkan waktu check-in
            ->get();

        return response()->json([
            'member' => $member, // Mengembalikan data member
            'total_sessions' => $sessions->count(), // Mengembalikan jumlah sesi
            'total_duration' => $sessions->sum('total_duration'), // Mengembalikan total durasi
            'average_duration' => $sessions->avg('total_duration'), // Mengembalikan rata-rata durasi
            'sessions' => $sessions
        ]);
    }

public function getAllMembers() // Fungsi untuk menampilkan semua anggota
{
    if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) { // Memeriksa peran pengguna
        return response()->json(['message' => 'Unauthorized'], 403); // Mengembalikan pesan kesalahan jika tidak memiliki akses
    }
    $members = Member::paginate(15); // Mengambil semua anggota
    return response()->json(['members' => $members]); // Mengembalikan daftar anggota
}

public function getMember($id) // Fungsi untuk menampilkan anggota berdasarkan ID
{
    $member = Member::findOrFail($id); // Mencari anggota berdasarkan ID
    return response()->json(['member' => $member]); // Mengembalikan data anggota
}

public function updateMember(Request $request, $id) // Fungsi untuk memperbarui anggota
{
    if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) { // Memeriksa peran pengguna
        return response()->json(['message' => 'Unauthorized'], 403); // Mengembalikan pesan kesalahan jika tidak memiliki akses
    }
    $member = Member::findOrFail($id); // Mencari anggota berdasarkan ID
    
    $validator = Validator::make($request->all(), [ // Validasi input
        'name' => 'string',
        'email' => 'email|unique:members,email,'.$id,
        'phone' => 'string',
        'status' => 'in:active,inactive'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400); // Mengembalikan pesan kesalahan
    }

    $member->update($request->all());
    return response()->json(['member' => $member]); //    Mengembalikan data anggota
}

public function deleteMember($id) // Fungsi untuk menghapus anggota
{
    if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) { // Memeriksa peran pengguna
        return response()->json(['message' => 'Unauthorized'], 403); // Mengembalikan pesan kesalahan
    }
    $member = Member::findOrFail($id); // Mencari anggota berdasarkan ID
    $member->delete();
    return response()->json(['message' => 'Member berhasil dihapus']);
}

public function getProfile() //     Fungsi untuk menampilkan profil anggota
{
    $member = auth()->user(); //    Mengambil data anggota yang sedang login
    return response()->json(['member' => $member]); // Mengembalikan data anggota
}

public function updateProfile(Request $request) // Fungsi untuk memperbarui profil anggota
{
    $member = auth()->user();
    
    $validator = Validator::make($request->all(), [
        'name' => 'string',
        'email' => 'email|unique:members,email,'.$member->id,
        'phone' => 'string'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $member->update($request->all());
    return response()->json(['member' => $member]);
}

public function membershipReport()
{
    $members = Member::selectRaw('
        membership_type,
        COUNT(*) as total,
        COUNT(CASE WHEN status = "active" THEN 1 END) as active,
        COUNT(CASE WHEN status = "inactive" THEN 1 END) as inactive
    ') // Mengambil data laporan keanggotaan
    ->groupBy('membership_type')
    ->get();

    return response()->json(['report' => $members]);
}

private function generateUniqueRFIDCardNumber() // Fungsi untuk membuat nomor kartu RFID unik
{
    do { //  Melakukan perulangan hingga ditemukan nomor kartu RFID unik
        $rfidCardNumber = 'RFID-' . Str::random(10); // Membuat nomor kartu RFID
    } while (Member::where('rfid_card_number', $rfidCardNumber)->exists()); // Memeriksa apakah nomor kartu RFID sudah ada

    return $rfidCardNumber;
}

private function calculateMembershipEndDate($membershipType) // Fungsi untuk menghitung tanggal akhir keanggotaan
{
    $durations = [ // Durasi keanggotaan
        'bronze' => 3,
        'platinum' => 6,
        'gold' => 12
    ];

    return now()->addMonths($durations[$membershipType] ?? 3);
}

private function checkAdminAccess() // Fungsi untuk memeriksa akses admin
{
    if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) { // Memeriksa peran pengguna
        return response()->json(['message' => 'Unauthorized'], 403); // Mengembalikan pesan kesalahan jika tidak memiliki akses
    }
    return true;
}
}
