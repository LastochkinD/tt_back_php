<?php

namespace app\controllers;

use Yii;
use app\models\User;
use app\models\LoginForm;

class AuthController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // CORS
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['https://tasktracker.timetocode.ru', 'http://localhost:3000', '*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
                'Access-Control-Allow-Methods' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
            ],
        ];

        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => \yii\web\Response::FORMAT_JSON,
            ],
        ];
        return $behaviors;
    }

    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }

    public function actionLogin()
    {
        try {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            $model = new LoginForm();
            $data = Yii::$app->request->getBodyParams();

            // Map email to username for LoginForm compatibility
            if (isset($data['email'])) {
                $data['username'] = $data['email'];
            }

            if ($model->load($data, '') && $model->validate()) {
                $user = $model->user;
                $token = Yii::$app->jwt->generateToken($user->id);

                return [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'token' => (string) $token,
                ];
            } else {
                return ['message' => 'Invalid credentials'];
            }
        } catch (\Exception $e) {
            return ['message' => 'Database connection error: ' . $e->getMessage()];
        }
    }

    public function actionRegister()
    {
        try {
            $user = new User(['scenario' => User::SCENARIO_REGISTER]);
            $data = Yii::$app->request->getBodyParams();

            if ($user->load($data, '') && $user->save()) {
                $token = Yii::$app->jwt->generateToken($user->id);

                Yii::$app->response->statusCode = 201;

                return [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'token' => (string) $token,
                ];
            }
            return ['message' => 'User already exists'];
        } catch (\Exception $e) {
            return ['message' => 'Database connection error: ' . $e->getMessage()];
        }
    }
}
