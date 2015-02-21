<?php

namespace yii\admin\models;

use yii;

abstract class SettingsTranslation extends FakeModel
{
	protected $model_attributes = ['lang_id'];

	public function getPrimaryKey($asArray=false) { return $asArray ? ['lang_id'] : 'lang_id'; }
	protected $settings_model;

	public function findItems() {
		if (empty($this->lang_id))
			throw new yii\base\ErrorException('lang id should be set first');
		SettingsTranslationItem::setTableName(static::tableName());
		return SettingsTranslationItem::find()->where(['lang_id' => $this->lang_id])->all();
	}
	protected function addAttribute($name) {
		$item = new SettingsTranslationItem();
		$item->setAttribute('lang_id', $this->lang_id);
		$item->setAttribute('name', $name);
		return $item;
	}

	protected $_form_name;
	public function formName()
	{
		if (!$this->_form_name) {
			$model = $this->settings_model;
			/* @var $model \yii\db\ActiveRecord */
			$model = new $model();

			foreach ($model->getBehaviors() as $beh) {
				if ($beh instanceof yii\admin\behaviors\TranslateBehavior && $beh->model == self::className()) {
					$trans_field = $beh->attribute;
				}
			}
			if (empty($trans_field))
				throw new yii\web\HttpException(500, 'TranslateBehavior is not found for ' . self::className());

			$this->_form_name = $model->formName() . '['.$trans_field.'][' . $this->lang_id . ']';
		}
		return $this->_form_name;
	}
}
