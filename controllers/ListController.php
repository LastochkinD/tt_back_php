<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\ListEntity;
use app\components\BoardAccessManager;

class ListController extends Controller
{
    /**
     * @var \app\components\BoardAccessManager
     */
    protected $boardAccessManager;

    public function init()
    {
        parent::init();
        $this->boardAccessManager = Yii::$app->get('boardAccessManager');
    }

    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::class,
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => null,
                    'Access-Control-Max-Age' => 86400,
                    'Access-Control-Expose-Headers' => [],
                ],
            ],
            'authFilter' => [
                'class' => \app\filters\AuthFilter::class,
            ],
        ];
    }

    public function actionIndex()
    {
        $userId = Yii::$app->user->id;

        // Handle both GET (query parameters) and POST (request data) methods
        if (Yii::$app->request->isGet) {
            $boardId = Yii::$app->request->get('board');
        } elseif (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $boardId = $data['board'] ?? $data['board_id'] ?? null;
        } else {
            return $this->asJson(['error' => 'Method not allowed'])->setStatusCode(405);
        }

        if (!$boardId) {
            return $this->asJson(['error' => 'board parameter is required'])->setStatusCode(400);
        }

        // Check if user has access to the board
        if (!$this->boardAccessManager->canView($boardId, $userId)) {
            return $this->asJson(['error' => 'Access denied'])->setStatusCode(403);
        }

        $lists = ListEntity::find()
            ->where(['board_id' => $boardId])
            ->all();

        return $this->asJson($lists);
    }

    public function actionView($id)
    {
        $userId = Yii::$app->user->id;

        $list = ListEntity::find()
            ->joinWith(['board.boardAccesses'])
            ->where(['lists.id' => $id])
            ->andWhere([
                'OR',
                ['boards.user_id' => $userId],
                ['board_accesses.user_id' => $userId]
            ])
            ->one();

        if (!$list) {
            return $this->asJson(['error' => 'List not found or access denied'])->setStatusCode(404);
        }

        return $this->asJson($list);
    }

    public function actionCreate()
    {
        $userId = Yii::$app->user->id;
        $data = Yii::$app->request->post();
        $boardId = $data['board'] ?? $data['board_id'] ?? null;

        if (!$boardId) {
            return $this->asJson(['error' => 'board is required'])->setStatusCode(400);
        }

        // Check if user can create lists on this board
        if (!$this->boardAccessManager->canEdit($boardId, $userId)) {
            return $this->asJson(['error' => 'Access denied'])->setStatusCode(403);
        }

        $list = new ListEntity();
        $list->load($data, '');

        if ($list->validate() && $list->save()) {
            return $this->asJson($list)->setStatusCode(201);
        } else {
            return $this->asJson(['errors' => $list->errors])->setStatusCode(400);
        }
    }

    public function actionUpdate($id)
    {
        $userId = Yii::$app->user->id;
        $data = Yii::$app->request->post();

        $list = ListEntity::find()
            ->joinWith(['board'])
            ->where(['lists.id' => $id])
            ->one();

        if (!$list) {
            return $this->asJson(['error' => 'List not found'])->setStatusCode(404);
        }

        // Check access
        if (!$this->boardAccessManager->canEdit($list->board_id, $userId)) {
            return $this->asJson(['error' => 'Access denied'])->setStatusCode(403);
        }

        $list->load($data, '');

        if ($list->validate() && $list->save()) {
            return $this->asJson($list);
        } else {
            return $this->asJson(['errors' => $list->errors])->setStatusCode(400);
        }
    }

    public function actionDelete($id)
    {
        $userId = Yii::$app->user->id;

        $list = ListEntity::find()
            ->joinWith(['board'])
            ->where(['lists.id' => $id])
            ->one();

        if (!$list) {
            return $this->asJson(['error' => 'List not found'])->setStatusCode(404);
        }

        // Check access
        if (!$this->boardAccessManager->canEdit($list->board_id, $userId)) {
            return $this->asJson(['error' => 'Access denied'])->setStatusCode(403);
        }

        $list->delete();
        return $this->asJson(['message' => 'List deleted successfully']);
    }
}
