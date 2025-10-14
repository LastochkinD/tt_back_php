<?php

namespace app\components;

use Yii;
use app\models\BoardAccess;

class BoardAccessManager extends \yii\base\Component
{
    public function checkAccess($boardId, $userId, $requiredRole = null)
    {
        $access = BoardAccess::find()
            ->where(['board_id' => $boardId, 'user_id' => $userId])
            ->one();

        if (!$access) {
            return false;
        }

        if ($requiredRole) {
            return $this->hasRole($access->role, $requiredRole);
        }

        return true;
    }

    public function getUserRole($boardId, $userId)
    {
        $access = BoardAccess::find()
            ->where(['board_id' => $boardId, 'user_id' => $userId])
            ->one();

        return $access ? $access->role : null;
    }

    public function canView($boardId, $userId)
    {
        return $this->checkAccess($boardId, $userId);
    }

    public function canEdit($boardId, $userId)
    {
        return $this->checkAccess($boardId, $userId, 'editor');
    }

    public function canAdmin($boardId, $userId)
    {
        return $this->checkAccess($boardId, $userId, 'admin');
    }

    private function hasRole($userRole, $requiredRole)
    {
        $roles = ['viewer' => 0, 'editor' => 1, 'admin' => 2];
        return isset($roles[$userRole]) && isset($roles[$requiredRole]) && $roles[$userRole] >= $roles[$requiredRole];
    }
}
