<?php

namespace yii\admin\models;

use yii;

class Message extends yii\db\ActiveRecord
{
	public static function tableName() {
		return 'yii_message';
	}

	public function rules() {
		return [
			[['language', 'translation'], 'trim'],
			[['language'], 'required'],
		];
	}

	public function getSource() {
		return $this->hasOne(MessageSource::className(), ['id' => 'id']);
	}
}
