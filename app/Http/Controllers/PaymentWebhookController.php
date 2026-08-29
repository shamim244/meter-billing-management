<?php

namespace App\Http\Controllers;

use App\Services\Payment\OnlinePaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle incoming payment gateway webhooks (Cashfree & PG drivers).
     */
    public function handle(Request $request, OnlinePaymentGatewayService $gatewayService): JsonResponse
    {
        $rawPayload = $request->getContent();
        
        // Cashfree & Razorpay webhook signature headers
        $signature = $request->header('x-webhook-signature')
            ?? $request->header('X-Webhook-Signature')
            ?? $request->header('X-Razorpay-Signature')
            ?? $request->header('X-Signature')
            ?? $request->input('signature');

        $timestamp = $request->header('x-webhook-timestamp')
            ?? $request->header('X-Webhook-Timestamp');

        $isTesting = app()->environment('testing');
        $hasSecret = $gatewayService->hasConfiguredWebhookSecret();

        // Enforce mandatory signature verification
        if (!$isTesting || $signature !== null || $hasSecret) {
            if (empty($signature) || !$gatewayService->verifyWebhookSignature($rawPayload, (string) $signature, $timestamp)) {
                Log::warning('Payment webhook rejected: signature verification failed or signature missing', [
                    'ip' => $request->ip(),
                    'has_signature' => !empty($signature),
                    'timestamp' => $timestamp,
                ]);
                return response()->json(['error' => 'Invalid or missing webhook signature.'], 400);
            }
        }

        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = $request->all();
        }

        try {
            $result = $gatewayService->processWebhook($payload);
            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cashfree payment webhook processing error: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $payload,
            ]);

            return response()->json([
                'error' => 'Internal server error during webhook processing.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
