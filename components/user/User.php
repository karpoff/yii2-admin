<?php

namespace yii\admin\components\user;

use yii\helpers\Url;

class User extends \yii\web\User
{
	public $type = 'own';
	public $params = [];

	public function __construct($config = []) {
		switch ($config['type'])
		{
			case 'simple':
				$this->identityClass = 'yii\admin\components\user\SimpleIdentity';
				break;
		}

		$this->loginUrl = Url::toRoute('/admin-user/login');

		parent::__construct($config);
	}

	public function getIsGuest()
	{
		return $this->getIdentity() === null;
	}

	public function getIsAdmin()
	{
		$identity = $this->getIdentity();
		if (isset($identity->admin))
			return $identity->admin;
		return false;
	}
}