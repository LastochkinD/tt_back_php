<?php

namespace app\components;

use Yii;

class JwtComponent extends \yii\base\Component
{
    public $secret;

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    /**
     * Create JWT signature
     */
    private function sign($header, $payload, $secret)
    {
        $data = $this->base64UrlEncode($header) . '.' . $this->base64UrlEncode($payload);
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }

    /**
     * Verify JWT signature
     */
    private function verify($token, $secret)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        $expectedSignature = $this->sign($this->base64UrlDecode($header), $this->base64UrlDecode($payload), $secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function generateToken($userId)
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'iss' => 'task-tracker-backend',
            'aud' => 'task-tracker-frontend',
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24 * 90, // 90 days
            'user_id' => $userId,
        ]);

        $signature = $this->sign($header, $payload, $this->secret);
        return $this->base64UrlEncode($header) . '.' . $this->base64UrlEncode($payload) . '.' . $signature;
    }

    public function validateToken($token)
    {
        if (!$this->verify($token, $this->secret)) {
            return false;
        }

        $parts = explode('.', $token);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        // Check issuer
        if (!isset($payload['iss']) || $payload['iss'] !== 'task-tracker-backend') {
            return false;
        }

        // Check audience
        if (!isset($payload['aud']) || $payload['aud'] !== 'task-tracker-frontend') {
            return false;
        }

        return $payload;
    }

    public function getUserIdFromToken($token)
    {
        $decoded = $this->validateToken($token);
        return $decoded && isset($decoded['user_id']) ? $decoded['user_id'] : null;
    }
}
