<?php

namespace yii\admin\components\user;
use yii\admin\YiiAdminModule;


class SimpleIdentity extends \yii\base\Object implements \yii\web\IdentityInterface
{
	public $id;
	public $username;
	public $password;
	public $authKey;
	public $accessToken;
	public $admin = false;

	public static function findIdentity($id)
	{
		foreach (self::users() as $user) {
			if ($user['id'] == $id)
				return new static($user);
		}
		return null;
	}

	public static function findIdentityByAccessToken($token, $type = null)
	{
		foreach (self::users() as $user) {
			if ($user['accessToken'] == $token)
				return new static($user);
		}
		return null;
	}

	public static function findByUsername($username)
	{
		foreach (self::users() as $user) {
			if (strcasecmp($user['username'], $username) === 0)
				return new static($user);
		}
		return null;
	}

	protected static function users()
	{
		/** @var YiiAdminModule $module */
		$module = YiiAdminModule::getInstance();
		$params = $module->user->params;

		if (isset($params['login']) && isset($params['password'])) {
			return [[
				'id' => 1,
				'username' => $params['login'],
				'password' => $params['password'],
				'authKey' => 'access_key_admin',
				'accessToken' => 'access-token-admin',
				'admin' => true,
			]];
		} else if (isset($params[0]) && isset($params[1]) && is_array($params[0]) && is_array($params[1])) {
			return [[
				'id' => 1,
				'username' => $params[0]['login'],
				'password' => $params[0]['password'],
				'authKey' => 'access_key_admin',
				'accessToken' => 'access-token-admin',
				'admin' => true,
			], [
				'id' => 2,
				'username' => $params[1]['login'],
				'password' => $params[1]['password'],
				'authKey' => 'access_key',
				'accessToken' => 'access-token',
				'admin' => false,
			]];
		}
		return [];
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