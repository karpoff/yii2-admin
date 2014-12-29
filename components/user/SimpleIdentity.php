<?php

namespace yii\admin\components\user;

class SimpleIdentity extends \yii\base\Object implements \yii\web\IdentityInterface
{
	public $id;
	public $username;
	public $password;
	public $authKey;
	public $accessToken;

	public static function findIdentity($id)
	{
		return $id == 1 ? new static(self::user()) : null;
	}

	public static function findIdentityByAccessToken($token, $type = null)
	{
		$user = self::user();
		if ($user['accessToken'] === $token) {
			return new static($user);
		}

		return null;
	}

	public static function findByUsername($username)
	{
		$user = self::user();
		if (strcasecmp($user['username'], $username) === 0) {
			return new static($user);
		}

		return null;
	}

	protected static function user()
	{
		return [
			'id' => 1,
			'username' => \Yii::$app->controller->module->user->params['login'],
			'password' => \Yii::$app->controller->module->user->params['password'],
			'authKey' => 'access_key',
			'accessToken' => 'access-token',
		];
	}
	public function getId()
	{
		return $this->id;
	}

	public function getAuthKey()
	{
		return $this->authKey;
	}

	public function validateAuthKey($authKey)
	{
		return $this->authKey === $authKey;
	}

	public function validatePassword($password)
	{
		return $this->password === $password;
	}
}