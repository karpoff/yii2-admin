<?php

namespace yii\admin\components;

use yii\admin\models\Lang;
use yii\base\Component;
use yii\base\InvalidConfigException;

class SettingsComponent extends Component {
	/** @var  \yii\admin\models\Settings */
	public $settings_model;

	/** @var \yii\admin\models\Settings */
	protected $_settings_model;
	/** @var \yii\admin\models\SettingsTranslation */
	protected $_settings_trans_model;

	private $_items;

	public function init() {
		if (!$this->settings_model)
			throw new InvalidConfigException('configure settings model');

		$this->_items = [];
		$settings = $this->settings_model;

		$this->_settings_model = new $settings();

		/** @var \yii\admin\models\SettingsItem $item */
		foreach ($this->_settings_model->findItems() as $item) {
			$this->_items[$item->getAttribute('name')] = $item->getAttribute('value');
		}
		/** @var \yii\admin\models\SettingsTranslation $trans */
		$trans = $this->_settings_model->getTrans();
		if ($trans) {
			$trans = $trans->modelClass;
			$this->_settings_trans_model = new $trans();
			$this->_settings_trans_model->setAttribute('lang_id', Lang::getCurrentId());
			/** @var \yii\admin\models\SettingsTranslationItem $item */
			foreach ($this->_settings_trans_model->findItems() as $item) {
				$this->_items[$item->getAttribute('name')] = $item->getAttribute('value');
			}
		}
	}

	public function get($name) {
		return isset($this->_items[$name]) ? $this->_items[$name] : null;
	}

	public function getRelated($name) {
		return $this->_settings_model->$name;
	}
}