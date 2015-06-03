<?php

namespace yii\admin\models;

use yii;

class MessageSource extends yii\db\ActiveRecord
{
	public static function tableName() {
		return 'yii_message_source';
	}

	public function rules() {
		return [
			[['message'], 'trim'],
			[['message'], 'required'],
		];
	}

	public function getMessages() {
		return $this->hasMany(Message::className(), ['id' => 'id']);
	}

	public function beforeDelete() {
		/** @var Message $message */
		foreach ($this->messages as $message) {
			$message->delete();
		}
		return true;
	}
}
