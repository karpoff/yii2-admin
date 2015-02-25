<?php
namespace yii\admin\behaviors;

use Yii;
use yii\admin\YiiAdminModule;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;
use yii\helpers\Html;

/**
 * TranslateBehavior automatically saves translation models.
 *
 * To use TranslateBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * use yii\admin\behaviors\TranslateBehavior;
 *
 * function behaviors()
 * {
 *     return [
 *         [
 *             'class' => TranslateBehavior::className(),
 *             'attribute' => 'text',
 *             'model' => 'app\models\TransText',
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Anton Karpov <karpoff@bk.ru>
 */
class TranslateBehavior extends Behavior
{
	public $attribute;
	public $scenarios = [];
	public $relation_lang = 'lang';
	public $relation_model = null;
	public $model;
	public $models;
	public $copyDefault = [];

	protected $_models = [];
	public $settings = [];

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			BaseActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
			BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
			BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
			BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
			BaseActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
		];
	}

	/**
	 * This method is invoked when validation ends.
	 */
	public function afterValidate()
	{
		/** @var BaseActiveRecord $model */
		$model = $this->owner;

		if (!in_array($model->getScenario(), $this->scenarios))
			return;

		/** @var \yii\admin\models\Translation $tran_model */
		$tran_model = $this->model;

		if (!$this->settings) {
			$this->settings['tran_lang_field'] = $tran_model::getRelationField(false);
			$this->settings['lang_tran_field'] = $tran_model::getRelationField(false, true);
			$this->settings['tran_model_field'] = $tran_model::getRelationField(true);
			$this->settings['model_tran_field'] = $tran_model::getRelationField(true, true);
		}

		$languages = YiiAdminModule::getInstance()->getLanguages();
		$language_ids = [];

		/* @var $lang \yii\admin\models\Lang */
		foreach ($languages as $lang) {
			$language_ids[] = $lang->getAttribute($this->settings['lang_tran_field']);
		}

		if ($this->models) {
			$f = $this->models;
			$this->_models = $f();
		} else {
			$query = $tran_model::find()->where([$this->settings['tran_lang_field'] => $language_ids]);
			if (isset($this->settings['tran_model_field']))
				$query->andWhere([$this->settings['tran_model_field'] => $model->getAttribute($this->settings['model_tran_field'])]);

			$this->_models = $query->all();
		}

		//create new translations if needed
		foreach ($language_ids as $lang_id) {
			$found = false;
			foreach ($this->_models as $trans) {
				if ($trans->getAttribute($this->settings['tran_lang_field']) == $lang_id) {
					$found = true;
					break;
				}
			}

			if (!$found) {
				/** @var \yii\admin\models\Translation $trans */
				$trans = new $this->model();
				$trans->setAttribute($this->settings['tran_lang_field'], $lang_id);
				if (isset($this->settings['tran_model_field']))
					$trans->setAttribute($this->settings['tran_model_field'], $model->getIsNewRecord() ? 0 : $model->getAttribute($this->settings['model_tran_field']));
				$this->_models[] = $trans;
			}
		}

		if (!is_array($model->{$this->attribute})) {
			return;
		}
		$default = null;
		foreach ($model->{$this->attribute} as $lang_id => $tran) {
			$trans = null;
			if (!$default) {
				$default = $tran;
			} else {
				if (is_array($this->copyDefault)) {
					foreach ($this->copyDefault as $attr) {
						if (empty($tran[$attr]))
							$tran[$attr] = $default[$attr];
					}
				} else if ($this->copyDefault === true) {
					$fbd = $model->getPrimaryKey(true);
					if (isset($this->settings['tran_model_field']))
						$fbd[] = $this->settings['tran_model_field'];
					$fbd[] = $this->settings['tran_lang_field'];
					foreach ($tran as $attr => $val) {
						if (array_search($attr, $fbd) === false && empty($tran[$attr])) {
							$tran[$attr] = $default[$attr];
						}
					}
				}
			}
			foreach ($this->_models as $trans_m) {
				if ($trans_m->getAttribute($this->settings['tran_lang_field']) == $lang_id) {
					$trans = $trans_m;
					break;
				}
			}

			if (!$trans)
				throw new InvalidConfigException(500, 'translation model is not existed/created');

			$trans->load($tran, '');
			$trans->validate();
			foreach ($trans->getErrors() as $name => $values) {
				$name = Html::getInputName($trans, $name);
				$name = $this->attribute . substr($name, strpos($name, ']') + 1);
				foreach ($values as $value) {
					$model->addError($name, $value);
				}
			}
		}
	}

	/**
	 * This method is called at the end of inserting or updating a record.
	 */
	public function afterSave()
	{
		/** @var BaseActiveRecord $model */
		$model = $this->owner;

		if (!in_array($model->getScenario(), $this->scenarios))
			return;

		/** @var \yii\admin\models\Translation $trans */
		foreach ($this->_models as $trans) {
			if (isset($this->settings['tran_model_field']) && $trans->getAttribute($this->settings['tran_model_field']) == 0) {
				$trans->setAttribute($this->settings['tran_model_field'], $model->getAttribute($this->settings['model_tran_field']));
			}
			$trans->save();
		}
	}

	/**
	 * This method is called before inserting or updating a record.
	 */
	public function beforeSave()
	{
		/** @var BaseActiveRecord $model */
		$model = $this->owner;

		if (!in_array($model->getScenario(), $this->scenarios))
			return;

		if ($model->hasAttribute($this->attribute)) {
			$model->setAttribute($this->attribute, $model->getOldAttribute($this->attribute));
		}
	}
	/**
	 * This method is invoked after deleting a record.
	 */
	public function afterDelete()
	{

	}

}