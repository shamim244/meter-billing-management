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
        
        // Cashfree webhook headers
        $signature = $request->header('x-webhook-signature')
            ?? $request->header('X-Webhook-Signature')
            ?? $request->header('X-Razorpay-Signature')
            ?? $request->header('X-Signature')
            ?? $request->input('signature');

        $timestamp = $request->header('x-webhook-timestamp')
            ?? $request->header('X-Webhook-Timestamp');

        // Verify signature if provided/configured
        if ($signature && !$gatewayService->verifyWebhookSignature($rawPayload, (string) $signature, $timestamp)) {
            Log::warning('Cashfree payment webhook signature verification failed', [
                'ip' => $request->ip(),
                'signature' => $signature,
                'timestamp' => $timestamp,
            ]);
            return response()->json(['error' => 'Invalid webhook signature.'], 400);
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
