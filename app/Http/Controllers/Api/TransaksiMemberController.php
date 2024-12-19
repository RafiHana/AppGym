<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\TransaksiMember;
use App\Models\PaketMember;
use App\Services\WebhookService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class TransaksiMemberController extends Controller
{
    public function __construct()
    {
        // Pastikan hanya admin yang bisa mengakses renewMembership
        $this->middleware(['auth:sanctum', 'role:admin,superadmin'])->only(['renewMembership']);
    }

    public function renewMembership(Request $request) // Memoerbarui Paket Membership
    {
        // Tambahkan validasi untuk memastikan yang login adalah admin
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'superadmin'])) { // Memeriksa peran pengguna
            return response()->json(['message' => 'Unauthorized. Only admin can perform this action.'], 403); // Mengembalikan pesan kesalahan
        }

        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:members,id',
            'paket_member_id' => 'required|exists:paket_members,id',
            'payment_method' => 'required|in:cash,credit_card,debit_card'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $member = Member::findOrFail($request->member_id);
            $paket = PaketMember::findOrFail($request->paket_member_id);

            $transaction = TransaksiMember::create([
                'member_id' => $member->id,
                'paket_member_id' => $paket->id,
                'amount' => $paket->price,
                'payment_method' => $request->payment_method,
                'payment_status' => 'success',
                'processed_by' => auth()->id(), // ID admin yang melakukan proses
                'transaction_date' => now(),
                'notes' => "Processed by " . auth()->user()->name // Tambahkan catatan admin
            ]);

            // Update membership
            $member->update([
                'membership_type' => $paket->type,
                'membership_end_date' => $this->calculateNewExpirationDate($member, $paket->duration_months),
                'status' => 'active',
                'last_updated_by' => auth()->id() // Tambahkan ini di model Member jika ingin tracking
            ]);

            DB::commit();
            
            // Tambahkan informasi admin di response
            return response()->json([
                'transaction' => $transaction,
                'processed_by' => [
                    'id' => auth()->id(),
                    'name' => auth()->user()->name,
                    'role' => auth()->user()->role
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Renewal failed'], 500);
        }
    }

    public function getMembershipPackages()
    {
        $packages = PaketMember::all();
        return response()->json(['packages' => $packages]);
    }

    public function memberTransactionHistory()
    {
        $transactions = TransaksiMember::where('member_id', auth()->id())
            ->with('paketMember')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json(['transactions' => $transactions]);
    }

    public function revenueReport(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now();

        $revenue = TransaksiMember::whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('
                DATE(transaction_date) as date,
                COUNT(*) as total_transactions,
                SUM(amount) as total_revenue
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['report' => $revenue]);
    }

    private function calculateNewExpirationDate($member, $durationMonths)
    {
        $currentEndDate = $member->membership_end_date;
        $now = now();

        // Jika membership sudah expired, mulai dari tanggal sekarang
        if ($currentEndDate < $now) {
            return $now->addMonths($durationMonths);
        }

        // Jika masih aktif, tambahkan ke tanggal expired yang ada
        return Carbon::parse($currentEndDate)->addMonths($durationMonths);
    }
}
