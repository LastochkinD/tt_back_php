<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Comment;

class CommentController extends Controller
{
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

    public function actionCardComments($cardId)
    {
        $userId = Yii::$app->user->id;

        // Check if user has access to the board through the card
        $comment = Comment::find()
            ->joinWith(['card.list.board.boardAccesses'])
            ->where(['comments.card_id' => $cardId])
            ->andWhere([
                'OR',
                ['boards.user_id' => $userId],
                ['board_accesses.user_id' => $userId]
            ])
            ->one();

        if (!$comment && Comment::find()->where(['card_id' => $cardId])->exists()) {
            $this->response->statusCode = 403;
            return $this->asJson(['error' => 'Access denied']);
        }

        $comments = Comment::find()
            ->joinWith(['user'])
            ->where(['card_id' => $cardId])
            ->all();

        return $this->asJson($comments);
    }

    public function actionView($id)
    {
        $userId = Yii::$app->user->id;

        $comment = Comment::find()
            ->joinWith(['card.list.board.boardAccesses', 'user'])
            ->where(['comments.id' => $id])
            ->andWhere([
                'OR',
                ['boards.user_id' => $userId],
                ['board_accesses.user_id' => $userId]
            ])
            ->one();

        if (!$comment) {
            $this->response->statusCode = 404;
            return $this->asJson(['error' => 'Comment not found or access denied']);
        }

        return $this->asJson($comment);
    }

    public function actionCreate()
    {
        $userId = Yii::$app->user->id;
        $data = Yii::$app->request->post();
        $cardId = $data['cardId'] ?? null;

        if (!$cardId) {
            $this->response->statusCode = 400;
            return $this->asJson(['error' => 'cardId is required']);
        }

        // Check access through card's board
        $card = \app\models\Card::find()
            ->joinWith(['list.board.boardAccesses'])
            ->where(['cards.id' => $cardId])
            ->andWhere([
                'OR',
                ['boards.user_id' => $userId],
                ['board_accesses.user_id' => $userId]
            ])
            ->one();

        if (!$card) {
            $this->response->statusCode = 404;
            return $this->asJson(['error' => 'Card not found or access denied']);
        }

        if (!Yii::$app->boardAccessManager->canView($card->list->board_id, $userId)) {
            $this->response->statusCode = 403;
            return $this->asJson(['error' => 'Access denied']);
        }

        $comment = new Comment();
        $comment->load($data, '');
        $comment->card_id = $cardId;
        $comment->user_id = $userId;

        if ($comment->validate() && $comment->save()) {
            $comment->refresh();
            $comment->user; // Load user relation
            $this->response->statusCode = 201;
            return $this->asJson($comment);
        } else {
            $this->response->statusCode = 400;
            return $this->asJson(['errors' => $comment->errors]);
        }
    }

    public function actionUpdate($id)
    {
        $userId = Yii::$app->user->id;
        $data = Yii::$app->request->post();

        $comment = Comment::find()
            ->joinWith(['card.list.board'])
            ->where(['comments.id' => $id])
            ->andWhere(['comments.user_id' => $userId]) // Only author can update
            ->one();

        if (!$comment) {
            $this->response->statusCode = 404;
            return $this->asJson(['error' => 'Comment not found or access denied']);
        }

        // Additional access check through board
        if (!Yii::$app->boardAccessManager->canView($comment->card->list->board_id, $userId)) {
            $this->response->statusCode = 403;
            return $this->asJson(['error' => 'Access denied']);
        }

        $comment->load($data, '');

        if ($comment->validate() && $comment->save()) {
            $comment->refresh();
            $comment->user;
            return $this->asJson($comment);
        } else {
            $this->response->statusCode = 400;
            return $this->asJson(['errors' => $comment->errors]);
        }
    }

    public function actionDelete($id)
    {
        $userId = Yii::$app->user->id;

        $comment = Comment::find()
            ->joinWith(['card.list.board'])
            ->where(['comments.id' => $id])
            ->andWhere([
                'OR',
                ['comments.user_id' => $userId], // Author can delete
                ['boards.user_id' => $userId] // Board owner can delete
            ])
            ->one();

        if (!$comment) {
            $this->response->statusCode = 404;
            return $this->asJson(['error' => 'Comment not found or access denied']);
        }

        // Additional access check
        if (!Yii::$app->boardAccessManager->canView($comment->card->list->board_id, $userId)) {
            $this->response->statusCode = 403;
            return $this->asJson(['error' => 'Access denied']);
        }

        $comment->delete();
        return $this->asJson(['message' => 'Comment deleted']);
    }

    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        return [];
    }
}
