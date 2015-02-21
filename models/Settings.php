<?php
namespace yii\admin\models;

use yii\admin\behaviors\TranslateBehavior;


abstract class Settings extends FakeModel {
	protected $model_attributes = ['trans'];

	public function findItems() {
		SettingsItem::setTableName(static::tableName());
		return SettingsItem::find()->all();
	}
	protected function addAttribute($name) {
		$item = new SettingsItem();
		$item->setAttribute('name', $name);
		return $item;
	}

	public function getTrans() {
		/** @var \yii\db\ActiveRecord $this */
		foreach ($this->getBehaviors() as $beh) {
			if ($beh instanceof TranslateBehavior) {
				return $this->hasOne($beh->model, ['id' => 'id']);
			}
		}
		return null;
	}
}