<?php

namespace app\controllers;

use Yii;
use app\models\User;
use app\models\LoginForm;

class AuthController extends \yii\rest\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();



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
            return $user->getErrors();
        } catch (\Exception $e) {
            return ['message' => 'Database connection error: ' . $e->getMessage()];
        }
    }
}
