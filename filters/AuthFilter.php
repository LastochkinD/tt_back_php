<?php

namespace app\filters;

use Yii;
use yii\web\Controller;
use yii\base\ActionFilter;
use app\models\User;

class AuthFilter extends ActionFilter
{
    public function beforeAction($action)
    {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');

        if (!$authHeader) {
            Yii::$app->response->setStatusCode(401);
            Yii::$app->response->data = ['error' => 'Authorization header missing'];
            return false;
        }

        $token = str_replace('Bearer ', '', $authHeader);

        $userId = Yii::$app->jwt->getUserIdFromToken($token);

        if (!$userId) {
            Yii::$app->response->setStatusCode(401);
            Yii::$app->response->data = ['error' => 'Invalid or expired token'];
            return false;
        }

        // Set the current user in the application
        Yii::$app->user->identity = User::findIdentity($userId);

        if (!Yii::$app->user->identity) {
            Yii::$app->response->setStatusCode(401);
            Yii::$app->response->data = ['error' => 'User not found'];
            return false;
        }

        return true;
    }
}
