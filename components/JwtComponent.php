<?php

namespace app\components;

use Yii;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtComponent extends \yii\base\Component
{
    public $secret;

    public function generateToken($userId)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 60 * 60 * 24 * 90; // 90 days
        $payload = array(
            'iss' => 'task-tracker-backend',
            'aud' => 'task-tracker-frontend',
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $userId
        );

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserIdFromToken($token)
    {
        $decoded = $this->validateToken($token);
        return $decoded ? $decoded['user_id'] : null;
    }
}
