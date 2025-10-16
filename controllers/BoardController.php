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

        // Remove CORS from individual controllers to avoid conflicts with .htaccess

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

    public function actionMembers($id)
    {
        // Check if board exists and user has access
        $board = $this->findModel($id);
        $this->checkAccess('view', $board);

        // Get all board access entries for this board
        $accesses = BoardAccess::find()
            ->where(['board_id' => $id])
            ->with('user')
            ->all();

        // Format response
        $members = [];
        foreach ($accesses as $access) {
            $members[] = [
                'id' => $access->id,
                'role' => $access->role,
                'user' => [
                    'id' => $access->user->id,
                    'email' => $access->user->email,
                    'name' => $access->user->name,
                ],
            ];
        }

        // Include board owner if not already in members
        $ownerExists = false;
        foreach ($members as $member) {
            if ($member['user']['id'] === $board->user_id) {
                $ownerExists = true;
                break;
            }
        }

        if (!$ownerExists) {
            $members[] = [
                'id' => null,
                'role' => BoardAccess::ROLE_ADMIN,
                'user' => [
                    'id' => $board->owner->id,
                    'email' => $board->owner->email,
                    'name' => $board->owner->name,
                ],
            ];
        }

        return $members;
    }

    public function actionAddMember($id)
    {
        // Check if board exists and user has admin access
        $board = $this->findModel($id);

        // Check if current user is admin (owner or admin role)
        $currentUserId = Yii::$app->user->id;
        $isOwner = ($board->user_id === $currentUserId);
        $hasAdminRole = BoardAccess::find()->where([
            'board_id' => $id,
            'user_id' => $currentUserId,
            'role' => BoardAccess::ROLE_ADMIN
        ])->exists();

        if (!$isOwner && !$hasAdminRole) {
            throw new \yii\web\ForbiddenHttpException('Only administrators can manage board access');
        }

        $request = Yii::$app->request->post();

        // Support both userId (legacy) and email (new) parameters
        $userId = isset($request['userId']) ? $request['userId'] : null;
        $email = isset($request['email']) ? $request['email'] : null;

        if (!isset($request['role']) || (!$userId && !$email)) {
            throw new \yii\web\BadRequestHttpException('role is required, and either userId or email must be provided');
        }

        $role = $request['role'];

        // Find user by email (preferred), or userId (legacy)
        $user = null;
        if ($email) {
            $user = \app\models\User::findOne(['email' => $email]);
            if ($user === null) {
                throw new \yii\web\NotFoundHttpException('User with this email not found');
            }
        } elseif ($userId) {
            $user = \app\models\User::findOne($userId);
            if ($user === null) {
                throw new \yii\web\NotFoundHttpException('User with this ID not found');
            }
        }

        // Check if user already has access
        $existingAccess = BoardAccess::findOne(['board_id' => $id, 'user_id' => $user->id]);
        if ($existingAccess !== null) {
            throw new \yii\web\BadRequestHttpException('User already has access to this board');
        }

        // Create new access record
        $access = new BoardAccess([
            'board_id' => $id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        if ($access->save()) {
            Yii::$app->response->statusCode = 201;
            return [
                'message' => 'User added to board',
                'access' => [
                    'id' => $access->id,
                    'role' => $access->role,
                    'user' => [
                        'id' => $access->user->id,
                        'email' => $access->user->email,
                        'name' => $access->user->name,
                    ],
                ],
            ];
        }

        return $access;
    }

    public function actionUpdateMember($id, $memberId)
    {
        // Check if board exists
        $board = $this->findModel($id);

        // Check admin access
        $currentUserId = Yii::$app->user->id;
        $isOwner = ($board->user_id === $currentUserId);
        $hasAdminRole = BoardAccess::find()->where([
            'board_id' => $id,
            'user_id' => $currentUserId,
            'role' => BoardAccess::ROLE_ADMIN
        ])->exists();

        if (!$isOwner && !$hasAdminRole) {
            throw new \yii\web\ForbiddenHttpException('Only administrators can manage board access');
        }

        // Find access record
        $access = BoardAccess::findOne($memberId);
        if ($access === null || $access->board_id !== (int)$id) {
            throw new \yii\web\NotFoundHttpException('Access record not found');
        }

        $request = Yii::$app->request->post();
        if (!isset($request['role'])) {
            throw new \yii\web\BadRequestHttpException('role is required');
        }

        $access->role = $request['role'];

        if ($access->save()) {
            return [
                'message' => 'Member role updated',
                'access' => [
                    'id' => $access->id,
                    'role' => $access->role,
                    'user' => [
                        'id' => $access->user->id,
                        'email' => $access->user->email,
                        'name' => $access->user->name,
                    ],
                ],
            ];
        }

        return $access;
    }

    public function actionRemoveMember($id, $memberId)
    {
        // Check if board exists
        $board = $this->findModel($id);

        // Check admin access
        $currentUserId = Yii::$app->user->id;
        $isOwner = ($board->user_id === $currentUserId);
        $hasAdminRole = BoardAccess::find()->where([
            'board_id' => $id,
            'user_id' => $currentUserId,
            'role' => BoardAccess::ROLE_ADMIN
        ])->exists();

        if (!$isOwner && !$hasAdminRole) {
            throw new \yii\web\ForbiddenHttpException('Only administrators can manage board access');
        }

        // Find access record
        $access = BoardAccess::findOne($memberId);
        if ($access === null || $access->board_id !== (int)$id) {
            throw new \yii\web\NotFoundHttpException('Access record not found');
        }

        // Check if it's the last admin (can't remove last admin)
        if ($access->role === BoardAccess::ROLE_ADMIN) {
            $adminCount = BoardAccess::find()->where([
                'board_id' => $id,
                'role' => BoardAccess::ROLE_ADMIN
            ])->count();

            // If owner has access record with admin role, count as admin
            $ownerHasAccess = BoardAccess::find()->where([
                'board_id' => $id,
                'user_id' => $board->user_id,
                'role' => BoardAccess::ROLE_ADMIN
            ])->exists();

            $totalAdmins = $adminCount + ($board->user_id !== $access->user_id && $ownerHasAccess ? 1 : 0);

            if ($totalAdmins <= 1 && !$ownerHasAccess) {
                throw new \yii\web\BadRequestHttpException('Cannot remove the last administrator from the board');
            }
        }

        if ($access->delete() !== false) {
            return ['message' => 'Member removed from board'];
        }

        throw new \yii\web\ServerErrorHttpException('Failed to remove member');
    }

    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }
}
