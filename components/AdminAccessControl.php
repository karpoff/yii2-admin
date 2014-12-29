<?php

namespace yii\admin\components;

use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;

class AdminAccessControl extends AccessControl
{
	public function init()
	{
		$this->user = \Yii::$app->controller->module->user;
		parent::init();
	}

	protected function denyAccess($user)
	{
		if ($user->getIsGuest()) {
			$user->loginRequired(false);
		} else {
			throw new ForbiddenHttpException(\Yii::t('yii', 'You are not allowed to perform this action.'));
		}
	}
}