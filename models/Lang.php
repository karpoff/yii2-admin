<?php
namespace yii\admin\models;

use yii\db\ActiveRecord;
use yii\web\HttpException;


class Lang extends ActiveRecord implements LangInterface {
	public static function tableName() {
		return 'yii_admin_lang';
	}

	public function rules() {
		return [
			[['name', 'code'], 'trim'],
			[['name', 'code', 'enabled', 'admin'], 'required'],
			[['enabled', 'admin'], 'boolean'],
			[['sort'], 'integer'],
		];
	}

	public function isEnabled() { return (bool) $this->getAttribute('enabled'); }
	public function isAdmin() { return (bool) $this->getAttribute('admin'); }
	public function getId() { return $this->getAttribute('id'); }
	public function getCode() { return $this->getAttribute('code'); }
	public function getTitle() { return $this->getAttribute('name'); }
	public static function getAll() { return self::find()->where(['enabled' => 1])->orderBy('sort ASC')->all(); }
}

