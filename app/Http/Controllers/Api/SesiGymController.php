<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Models\SesiGym;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;


class SesiGymController extends Controller
{
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function checkIn(Request $request)
    {
        $member = Member::where('rfid_card_number', $request->rfid_card) // Mencari anggota berdasarkan nomor kartu RFID yang diberikan
                       ->where('status', 'active') //Memastikan status anggota adalah 'active'
                       ->first(); // Mengambil data anggota pertama yang cocok

        if (!$member) {
            return response()->json(['message' => 'Invalid or inactive member'], 400); // Jika anggota tidak ditemukan, kembalikan pesan kesalahan
        }

        if (!$member->isActive()) { // Cek ulang status keaktifan anggota menggunakan method isActive()
            return response()->json(['message' => 'Membership has expired'], 400); // Jika keanggotaan sudah kedaluwarsa, kembalikan pesan kesalahan
        }

        $activeSession = SesiGym::where('member_id', $member->id) // Mencari sesi gym yang masih aktif untuk anggota tersebut
                               ->whereNull('check_out_time') // Memeriksa apakah ada sesi yang belum di-check-out
                               ->first();

        if ($activeSession) {
            return response()->json(['message' => 'Already checked in'], 400); // Jika sudah check-in sebelumnya, kembalikan pesan kesalahan
        }

        $session = SesiGym::create([ // Membuat entri sesi gym baru
            'member_id' => $member->id,   // Menyimpan ID anggota
            'check_in_time' => now(), // Mencatat waktu check-in saat ini
            'status' => 'active', // Mengatur status sesi menjadi 'active'
            'device_id' => $request->header('X-Device-ID'), // Menyimpan ID perangkat dari header request
            'verified_by' => auth()->id() //Mencatat siapa yang memverifikasi check-in (pengguna yang sedang login)
        ]);

        // Update last check-in time
        $member->update([
            'last_check_in' => now(), // terakhir check-in
            'total_check_ins' => $member->total_check_ins + 1 // total check-in
        ]);

        return response()->json(['session' => $session]); // Mengeluarkan respon dengan data sesi gym
    }

    public function checkOut(Request $request)
    {
        $member = Member::where('rfid_card_number', $request->rfid_card)->first(); // Mencari anggota berdasarkan nomor kartu RFID yang diberikan
        
        if (!$member) {
            return response()->json(['message' => 'Member Tidak Ditemukan'], 404); // Jika anggota tidak ditemukan, kembalikan respons error 404
        }

        $activeSession = SesiGym::where('member_id', $member->id) // Mencari sesi gym yang masih aktif untuk anggota tersebut
                               ->whereNull('check_out_time') // Memeriksa apakah ada sesi yang belum di-check-out
                               ->first();  // Mengambil id yang  di inginkan 

        if (!$activeSession) {
            return response()->json(['message' => 'Tidak Ada sesi Aktif'], 400);
        }

        $activeSession->update([
            'check_out_time' => now(), // Memperbarui waktu check-out
            'total_duration' => now()->diffInMinutes($activeSession->check_in_time), // Menghitung durasi
            'status' => 'completed' // Mengatur status sesi menjadi 'completed'
        ]);

        return response()->json(['session' => $activeSession]);
    }

    public function sessionHistory(Request $request)
    {
        $sessions = SesiGym::with('member')
                          ->when($request->member_id, function($query, $memberId) { // Menyaring sesi berdasarkan ID anggota
                              return $query->where('member_id', $memberId); // Menyaring sesi berdasarkan ID anggota
                          })
                          ->when($request->date, function($query, $date) { // Menyaring sesi berdasarkan tanggal
                              return $query->whereDate('check_in_time', $date);  // Menyaring sesi berdasarkan tanggal
                          })
                          ->orderBy('check_in_time', 'desc') // Mengurutkan sesi berdasarkan waktu check-in
                          ->paginate(15); // Mengambil sesi dengan paginasi

        return response()->json(['sessions' => $sessions]);
    }

    public function gymUsageReport(Request $request)
    {
        $startDate = $request->start_date ?? now()->subMonth(); // Mengatur tanggal awal
        $endDate = $request->end_date ?? now(); // Mengatur tanggal akhir

        $sessions = SesiGym::whereBetween('check_in_time', [$startDate, $endDate]) // Memeriksa sesi gym yang terdaftar pada tanggal yang ditentukan
                          ->with('member') // Memeriksa sesi gym yang terdaftar
                          ->get(); 

        $report = [
            'total_sessions' => $sessions->count(),
            'total_duration' => $sessions->sum('total_duration'),
            'average_duration' => $sessions->avg('total_duration'),
            'unique_members' => $sessions->unique('member_id')->count(),
            'daily_stats' => $sessions->groupBy(function($session) {
                return $session->check_in_time->format('Y-m-d');
            })->map(function($daySessions) {
                return [
                    'count' => $daySessions->count(),
                    'total_duration' => $daySessions->sum('total_duration'),
                    'unique_members' => $daySessions->unique('member_id')->count()
                ];
            })
        ];

        return response()->json(['report' => $report]);
    }

    public function getCurrentOccupancy()
    {
        $activeMembers = SesiGym::whereNull('check_out_time')
                               ->with('member')
                               ->get();

        return response()->json([
            'current_occupancy' => $activeMembers->count(),
            'active_members' => $activeMembers
        ]);
    }

}