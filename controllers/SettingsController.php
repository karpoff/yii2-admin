<?php

namespace yii\admin\controllers;

use Yii;
use yii\admin\models\SettingsTranslation;
use yii\admin\YiiAdminModule;
use yii\base\InvalidConfigException;

class SettingsController extends ModelController
{
	public $settings_component;

	public function listActions() { return []; }
	public function editActions() { return []; }

	protected $settings;

	public function init() {
		if (!$this->settings_component)
			throw new InvalidConfigException('Settings component is not properly configured');

		/** @var \yii\admin\components\SettingsComponent $settings */
		$settings = Yii::$app->{$this->settings_component};

		$this->model = $settings->settings_model;
		parent::init();
		$this->scenarios = true;
	}

	public final function editFieldFormats() {
		/** @var \yii\admin\components\SettingsComponent $settings */
		/*$settings = Yii::$app->{$this->settings_component};

		$out = [];
		if ($settings->settings_translation_table) {

		}*/
		return [];
	}

	public function actionList() {
		return $this->actionEdit(null);
	}

	public static function modelTitle($model) {
		return 'Settings';
	}

	public function getFormFields()
	{
		$fields = parent::getFormFields();

		$trans_relation = $this->model->getRelation('trans', false);

		if (!$trans_relation) {
			return $fields;
		}

		$trans = null;
		if (isset($fields['tabs'])) {
			foreach ($fields['tabs'] as &$fs) {
				foreach ($fs as $name => &$value) {
					if ($name === 'trans') {
						$trans = &$value;
						break;
					}
				}
				if ($trans)
					break;
			}
		} else {
			foreach ($fields['fields'] as $name => &$value) {
				if ($name == 'trans') {
					$trans = &$value;
					break;
				}
			}
		}

		/** @var \yii\admin\models\SettingsTranslation $model_class */
		$model_class = $trans_relation->modelClass;

		$trans['class'] = 'Translation';
		if (!isset($trans['controller']))
			$trans['controller'] = 'yii\admin\controllers\ModelController';
		if (!isset($trans['controller_params']))
			$trans['controller_params'] = [];
		$trans['controller_params']['model_class'] = $trans_relation->modelClass;

		$trans['models'] = [];
		/* @var $lang \yii\admin\models\Lang */
		foreach (YiiAdminModule::getInstance()->getLanguages() as $lang) {
			$st = new $model_class();
			$st->setAttribute('lang_id', $lang->getPrimaryKey());
			$trans['models'][$lang->getPrimaryKey()] = $st;
		}

		return $fields;
	}
}
