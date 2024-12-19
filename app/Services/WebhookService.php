<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;

class WebhookService
{
    /**
     * Mengirim notifikasi ke semua endpoint yang terdaftar
     */
    public function sendNotification($event, $data)
    {
        $endpoints = WebhookEndpoint::where('events', 'like', "%$event%")
                                   ->where('is_active', true)
                                   ->get();

        foreach ($endpoints as $endpoint) {
            $this->sendToEndpoint($endpoint, $event, $data);
        }
    }

    /**
     * Mengirim data ke endpoint spesifik
     */
    private function sendToEndpoint(WebhookEndpoint $endpoint, $event, $data)
    {
        try {
            $payload = [
                'event' => $event,
                'data' => $data,
                'timestamp' => now()->timestamp,
                'gym_id' => config('app.gym_id') // ID unik untuk gym Anda
            ];

            $signature = $this->generateSignature($payload, $endpoint->secret);

            $response = Http::timeout(5) // timeout setelah 5 detik
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event
                ])
                ->post($endpoint->url, $payload);

            // Log response
            $this->logWebhook($endpoint, $event, $payload, $response);

            if (!$response->successful()) {
                throw new \Exception("Webhook failed with status: {$response->status()}");
            }

        } catch (\Exception $e) {
            Log::error('Webhook failed', [
                'endpoint' => $endpoint->url,
                'event' => $event,
                'error' => $e->getMessage()
            ]);

            // Log kegagalan
            $this->logWebhook($endpoint, $event, $payload, null, $e->getMessage());
        }
    }

    /**
     * Generate signature untuk keamanan
     */
    private function generateSignature($payload, $secret)
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Log aktivitas webhook
     */
    private function logWebhook($endpoint, $event, $payload, $response = null, $error = null)
    {
        WebhookLog::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => $event,
            'payload' => $payload,
            'response' => $response ? $response->body() : null,
            'status' => $response ? $response->status() : 500,
            'error' => $error,
            'sent_at' => now()
        ]);
    }

    /**
     * Contoh event yang bisa dikirim:
     */
    public function notifyCheckIn($member, $session)
    {
        $this->sendNotification('member.check_in', [
            'member_id' => $member->id,
            'member_name' => $member->name,
            'check_in_time' => $session->check_in_time,
            'device_id' => $session->device_id
        ]);
    }

    public function notifyCheckOut($member, $session)
    {
        $this->sendNotification('member.check_out', [
            'member_id' => $member->id,
            'member_name' => $member->name,
            'check_out_time' => $session->check_out_time,
            'duration' => $session->total_duration,
            'device_id' => $session->device_id
        ]);
    }

    public function notifyMembershipExpiring($member)
    {
        $this->sendNotification('membership.expiring', [
            'member_id' => $member->id,
            'member_name' => $member->name,
            'expiry_date' => $member->membership_end_date,
            'days_remaining' => now()->diffInDays($member->membership_end_date)
        ]);
    }

    public function notifyMembershipRenewed($member, $transaction)
    {
        $this->sendNotification('membership.renewed', [
            'member_id' => $member->id,
            'member_name' => $member->name,
            'package_type' => $transaction->paket_member->type,
            'new_expiry_date' => $member->membership_end_date,
            'transaction_id' => $transaction->id
        ]);
    }
}