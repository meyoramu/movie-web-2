<?php

namespace CineVerse\Services;

use CineVerse\Core\Application;
use GuzzleHttp\Client;
use Exception;

/**
 * Payment Service
 * 
 * Handles payment processing for MTN Mobile Money and Airtel Money
 */
class PaymentService
{
    private Application $app;
    private Client $httpClient;
    private array $mtnConfig;
    private array $airtelConfig;

    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->httpClient = new Client();
        $this->mtnConfig = $this->app->config('app.payment.mtn');
        $this->airtelConfig = $this->app->config('app.payment.airtel');
    }

    /**
     * Process MTN Mobile Money payment
     */
    public function processMtnPayment(array $paymentData): array
    {
        try {
            // Generate transaction reference
            $transactionRef = $this->generateTransactionRef();
            
            // Prepare payment request
            $requestData = [
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'RWF',
                'externalId' => $transactionRef,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $this->formatPhoneNumber($paymentData['phone'])
                ],
                'payerMessage' => $paymentData['description'] ?? 'CineVerse Subscription',
                'payeeNote' => 'Payment for CineVerse services'
            ];

            // Get access token
            $accessToken = $this->getMtnAccessToken();

            // Make payment request
            $response = $this->httpClient->post($this->mtnConfig['api_url'] . '/collection/v1_0/requesttopay', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Reference-Id' => $transactionRef,
                    'X-Target-Environment' => 'sandbox', // Change to 'live' for production
                    'Content-Type' => 'application/json',
                    'Ocp-Apim-Subscription-Key' => $this->mtnConfig['subscription_key']
                ],
                'json' => $requestData
            ]);

            if ($response->getStatusCode() === 202) {
                // Payment request accepted, now check status
                return [
                    'success' => true,
                    'transaction_id' => $transactionRef,
                    'status' => 'pending',
                    'message' => 'Payment request sent successfully'
                ];
            }

            throw new Exception('Payment request failed');

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process Airtel Money payment
     */
    public function processAirtelPayment(array $paymentData): array
    {
        try {
            // Generate transaction reference
            $transactionRef = $this->generateTransactionRef();
            
            // Get access token
            $accessToken = $this->getAirtelAccessToken();

            // Prepare payment request
            $requestData = [
                'reference' => $transactionRef,
                'subscriber' => [
                    'country' => $paymentData['country'] ?? 'RW',
                    'currency' => $paymentData['currency'] ?? 'RWF',
                    'msisdn' => $this->formatPhoneNumber($paymentData['phone'])
                ],
                'transaction' => [
                    'amount' => $paymentData['amount'],
                    'country' => $paymentData['country'] ?? 'RW',
                    'currency' => $paymentData['currency'] ?? 'RWF',
                    'id' => $transactionRef
                ]
            ];

            // Make payment request
            $response = $this->httpClient->post($this->airtelConfig['api_url'] . '/merchant/v1/payments/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'X-Country' => $paymentData['country'] ?? 'RW',
                    'X-Currency' => $paymentData['currency'] ?? 'RWF'
                ],
                'json' => $requestData
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200 && isset($responseData['status'])) {
                return [
                    'success' => true,
                    'transaction_id' => $transactionRef,
                    'status' => strtolower($responseData['status']),
                    'message' => 'Payment request sent successfully'
                ];
            }

            throw new Exception('Payment request failed');

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check MTN payment status
     */
    public function checkMtnPaymentStatus(string $transactionId): array
    {
        try {
            $accessToken = $this->getMtnAccessToken();

            $response = $this->httpClient->get(
                $this->mtnConfig['api_url'] . '/collection/v1_0/requesttopay/' . $transactionId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'X-Target-Environment' => 'sandbox',
                        'Ocp-Apim-Subscription-Key' => $this->mtnConfig['subscription_key']
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'status' => strtolower($data['status'] ?? 'unknown'),
                'data' => $data
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check Airtel payment status
     */
    public function checkAirtelPaymentStatus(string $transactionId): array
    {
        try {
            $accessToken = $this->getAirtelAccessToken();

            $response = $this->httpClient->get(
                $this->airtelConfig['api_url'] . '/standard/v1/payments/' . $transactionId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                        'X-Country' => 'RW',
                        'X-Currency' => 'RWF'
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'status' => strtolower($data['status'] ?? 'unknown'),
                'data' => $data
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get MTN access token
     */
    private function getMtnAccessToken(): string
    {
        $cache = $this->app->get('cache');
        $cacheKey = 'mtn_access_token';
        
        $token = $cache->get($cacheKey);
        if ($token) {
            return $token;
        }

        try {
            $response = $this->httpClient->post($this->mtnConfig['api_url'] . '/collection/token/', [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => $this->mtnConfig['subscription_key'],
                    'Authorization' => 'Basic ' . base64_encode($this->mtnConfig['primary_key'] . ':' . $this->mtnConfig['secondary_key'])
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $token = $data['access_token'];

            // Cache token for 50 minutes (expires in 1 hour)
            $cache->set($cacheKey, $token, 3000);

            return $token;

        } catch (Exception $e) {
            throw new Exception('Failed to get MTN access token: ' . $e->getMessage());
        }
    }

    /**
     * Get Airtel access token
     */
    private function getAirtelAccessToken(): string
    {
        $cache = $this->app->get('cache');
        $cacheKey = 'airtel_access_token';
        
        $token = $cache->get($cacheKey);
        if ($token) {
            return $token;
        }

        try {
            $response = $this->httpClient->post($this->airtelConfig['api_url'] . '/auth/oauth2/token', [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'client_id' => $this->airtelConfig['client_id'],
                    'client_secret' => $this->airtelConfig['client_secret'],
                    'grant_type' => 'client_credentials'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $token = $data['access_token'];

            // Cache token for 50 minutes (expires in 1 hour)
            $cache->set($cacheKey, $token, 3000);

            return $token;

        } catch (Exception $e) {
            throw new Exception('Failed to get Airtel access token: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique transaction reference
     */
    private function generateTransactionRef(): string
    {
        return 'CV_' . date('YmdHis') . '_' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Format phone number for payment APIs
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            $phone = '25' . $phone; // Rwanda country code
        } elseif (strlen($phone) === 10 && substr($phone, 0, 2) === '07') {
            $phone = '25' . substr($phone, 1); // Remove leading 0 and add country code
        }
        
        return $phone;
    }

    /**
     * Create payment transaction record
     */
    public function createTransaction(array $data): int
    {
        $db = $this->app->get('database');
        
        return $db->insert('payment_transactions', [
            'uuid' => $this->generateUuid(),
            'user_id' => $data['user_id'],
            'payment_method' => $data['payment_method'],
            'transaction_type' => $data['transaction_type'] ?? 'subscription',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'RWF',
            'status' => 'pending',
            'external_transaction_id' => $data['transaction_id'] ?? null,
            'phone_number' => $data['phone'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? [])
        ]);
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus(string $transactionId, string $status, array $metadata = []): bool
    {
        $db = $this->app->get('database');
        
        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'completed') {
            $updateData['processed_at'] = date('Y-m-d H:i:s');
        }
        
        if (!empty($metadata)) {
            $updateData['metadata'] = json_encode($metadata);
        }
        
        return $db->table('payment_transactions')
            ->where('external_transaction_id', $transactionId)
            ->update($updateData) > 0;
    }

    /**
     * Generate UUID
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
