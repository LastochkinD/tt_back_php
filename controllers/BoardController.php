<?php

namespace app\controllers;

use Yii;
use app\models\Board;
use app\models\BoardAccess;

class BoardController extends \yii\rest\ActiveController
{
    public $modelClass = Board::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // JWT Authentication
        $behaviors['authenticator'] = [
            'class' => \app\filters\AuthFilter::class,
            'except' => ['options'],
        ];

        // CORS
        $behaviors['cors'] = [
            'class' => \yii\filters\Cors::class,
        ];

        return $behaviors;
    }

    // Override actions for custom logic
    public function actions()
    {
        $actions = parent::actions();
        // Customize actions as needed
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']); // We'll implement these custom
        return $actions;
    }

    public function actionIndex()
    {
        $userId = Yii::$app->user->id;

        // Get boards where user is owner (since board_access table may not exist)
        $boards = Board::find()
            ->where(['user_id' => $userId])
            ->all();

        $result = [];
        foreach ($boards as $board) {
            // For current implementation, all users are admin for all boards
            $result[] = [
                'id' => $board->id,
                'title' => $board->title,
                'description' => $board->description,
                'userId' => $board->user_id,
                'createdAt' => $board->created_at,
                'updatedAt' => $board->updated_at,
                'owner' => [
                    'id' => $board->owner->id,
                    'email' => $board->owner->email,
                    'name' => $board->owner->name,
                ],
                'userRole' => BoardAccess::ROLE_ADMIN,
                'accessId' => null,
            ];
        }

        return $result;
    }

    public function actionCreate()
    {
        $board = new Board();
        $board->load(Yii::$app->request->post(), '');
        $board->user_id = Yii::$app->user->id;

        if ($board->save()) {
            // Create board access for owner
            $access = new BoardAccess([
                'board_id' => $board->id,
                'user_id' => Yii::$app->user->id,
                'role' => BoardAccess::ROLE_ADMIN,
            ]);
            $access->save();

            // Return board with extended information as per documentation
            $boardData = [
                'id' => $board->id,
                'title' => $board->title,
                'description' => $board->description,
                'userId' => $board->user_id,
                'createdAt' => $board->created_at,
                'updatedAt' => $board->updated_at,
                'owner' => [
                    'id' => $board->user_id,
                    'email' => $board->owner->email,
                    'name' => $board->owner->name,
                ],
                'userRole' => BoardAccess::ROLE_ADMIN,
                'accessId' => $access->id,
            ];

            Yii::$app->response->statusCode = 201;
            return $boardData;
        }
        return $board;
    }

    public function findModel($id)
    {
        $model = Board::findOne($id);
        if ($model === null) {
            throw new \yii\web\NotFoundHttpException('Board not found');
        }
        return $model;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['view', 'update', 'delete'])) {
            $boardId = $model ? $model->id : Yii::$app->request->get('id');
            if (!Yii::$app->boardAccessManager->canView($boardId, Yii::$app->user->id)) {
                throw new \yii\web\ForbiddenHttpException('Access denied.');
            }
        }
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess('view', $model);

        // Get user's role and access ID
        $userId = Yii::$app->user->id;
        $userRole = BoardAccess::ROLE_VIEWER;
        $accessId = null;

        // Check if user is owner
        if ($model->user_id == $userId) {
            $userRole = BoardAccess::ROLE_ADMIN;
        } else {
            // Check board access table
            $access = BoardAccess::findOne(['board_id' => $id, 'user_id' => $userId]);
            if ($access) {
                $userRole = $access->role;
                $accessId = $access->id;
            }
        }

        // Return extended board data
        return [
            'id' => $model->id,
            'title' => $model->title,
            'description' => $model->description,
            'userId' => $model->user_id,
            'createdAt' => $model->created_at,
            'updatedAt' => $model->updated_at,
            'owner' => [
                'id' => $model->owner->id,
                'email' => $model->owner->email,
                'name' => $model->owner->name,
            ],
            'userRole' => $userRole,
            'accessId' => $accessId,
        ];
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess('update', $model);

        $model->load(Yii::$app->request->post(), '');
        if ($model->save()) {
            // Get user's role and access ID for response
            $userId = Yii::$app->user->id;
            $userRole = BoardAccess::ROLE_VIEWER;
            $accessId = null;

            if ($model->user_id == $userId) {
                $userRole = BoardAccess::ROLE_ADMIN;
            } else {
                $access = BoardAccess::findOne(['board_id' => $id, 'user_id' => $userId]);
                if ($access) {
                    $userRole = $access->role;
                    $accessId = $access->id;
                }
            }

            return [
                'id' => $model->id,
                'title' => $model->title,
                'description' => $model->description,
                'userId' => $model->user_id,
                'createdAt' => $model->created_at,
                'updatedAt' => $model->updated_at,
                'owner' => [
                    'id' => $model->owner->id,
                    'email' => $model->owner->email,
                    'name' => $model->owner->name,
                ],
                'userRole' => $userRole,
                'accessId' => $accessId,
            ];
        }
        return $model;
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $this->checkAccess('delete', $model);

        if ($model->delete() !== false) {
            Yii::$app->response->setStatusCode(204);
        }
    }

    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }
}
