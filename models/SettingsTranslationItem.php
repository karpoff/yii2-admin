<?php
namespace yii\admin\models;

use yii\db\ActiveRecord;


class SettingsTranslationItem extends ActiveRecord {
	protected static $tableName;

	public static function tableName() {
		return static::$tableName;
	}

	public static function setTableName($name) {
		if (!static::$tableName)
			static::$tableName = $name;
	}

	public function rules() {
		return [
			[['name', 'value'], 'trim'],
			[['name', 'lang_id'], 'required'],
		];
	}
}