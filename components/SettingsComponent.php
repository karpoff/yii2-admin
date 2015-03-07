<?php

namespace yii\admin\components;

use yii;
use yii\admin\models\Lang;
use yii\base\Component;
use yii\base\InvalidConfigException;

class SettingsComponent extends Component {
	/** @var  \yii\admin\models\Settings */
	public $settings_model;

	/** @var bool */
	public $cache = true;
	public $cache_time = 3600;

	/** @var \yii\admin\models\Settings */
	protected $_settings_model;

	private $_items;

	public function init() {
		if (!$this->settings_model)
			throw new InvalidConfigException('configure settings model');
	}

	public function get($name) {
		if (!$this->_items) {
			if ($this->cache) {
				$cached = Yii::$app->cache->get($this->getCacheKey());

				if ($cached)
					$this->_items = $cached;
			}


			if (!$this->_items) {
				$this->_items = [];

				$settings = $this->getSettingsModel();

				/** @var \yii\admin\models\SettingsItem $item */
				foreach ($settings->findItems() as $item) {
					$this->_items[$item->getAttribute('name')] = $item->getAttribute('value');
				}
				/** @var \yii\admin\models\SettingsTranslation $trans */
				$trans = $settings->getTrans();
				if ($trans) {
					$trans = $trans->modelClass;
					$trans = new $trans();
					$trans->setAttribute('lang_id', Lang::getCurrentId());
					/** @var \yii\admin\models\SettingsTranslationItem $item */
					foreach ($trans->findItems() as $item) {
						$this->_items[$item->getAttribute('name')] = $item->getAttribute('value');
					}
				}

				if ($this->cache) {
					Yii::$app->cache->set($this->getCacheKey(), $this->_items, $this->cache_time);
				}
			}
		}

		return isset($this->_items[$name]) ? $this->_items[$name] : null;
	}

	public function getRelated($name) {
		return $this->getSettingsModel()->$name;
	}

	public function clearCache() {
		if ($this->getSettingsModel()->getTrans()) {
			/** @var \yii\admin\models\Lang $lang */
			foreach (Lang::getAll() as $lang) {
				Yii::$app->cache->delete($this->getCacheKey($lang->getAttribute('id')));
			}
		}
		else {
			Yii::$app->cache->delete($this->getCacheKey());
		}
	}

	/**
	 * Returns settings model object.
	 * @return \yii\admin\models\Settings
	 */
	protected function getSettingsModel() {
		if (!$this->_settings_model) {
			$settings = $this->settings_model;
			$this->_settings_model = new $settings();
		}
		return $this->_settings_model;
	}
	/**
	 * Returns the cache key for settings.
	 * @param integer $lang_id
	 * @return mixed the cache key
	 */
	protected function getCacheKey($lang_id = 0)
	{
		return [
			__CLASS__,
			$this->settings_model,
			$lang_id ? $lang_id : ($this->getSettingsModel()->getTrans() ? Lang::getCurrentId() : $lang_id),
		];
	}
}