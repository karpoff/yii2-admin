<?php

namespace yii\admin\components;

use yii\admin\models\Lang;
use yii\base\Component;
use yii\base\InvalidConfigException;

class SettingsComponent extends Component {
	/** @var  \yii\admin\models\Settings */
	public $settings_model;

	public function init() {
		if (!$this->settings_model)
			throw new InvalidConfigException('configure settings model');
	}

	private $_items;
	public function get($name) {
		if ($this->_items === null) {
			$this->_items = [];
			$settings = $this->settings_model;

			/** @var \yii\admin\models\Settings $m */
			$m = new $settings();

			/** @var \yii\admin\models\SettingsItem $item */
			foreach ($m->findItems() as $item) {
				$this->_items[$item->getAttribute('name')] = $item->getAttribute('value');
			}
			/** @var \yii\admin\models\SettingsTranslation $trans */
			$trans = $m->getTrans();
			if ($trans) {
				$trans = $trans->modelClass;
				$trans = new $trans();
				$trans->setAttribute('lang_id', Lang::getCurrentId());
				/** @var \yii\admin\models\SettingsTranslationItem $item */
				foreach ($trans->findItems() as $item) {
					$this->_items[$item->getAttribute('name')] = $item->getAttribute('value');
				}
			}
		}

		return isset($this->_items[$name]) ? $this->_items[$name] : null;
	}
}