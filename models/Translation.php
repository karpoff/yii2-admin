<?php
namespace yii\admin\models;

use yii\admin\behaviors\TranslateBehavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;

abstract class Translation extends ActiveRecord {

	/**
	 * returns one of the relation tables field according to arguments
	 * @param bool $is_model if true returns fields for main model, otherwise for Language model
	 * @param bool $outer if true return outer field id (in main or Lang model), if false return field of Translate model
	 * @return string
	 */
	public static function getRelationField($is_model = true, $outer = false) {
		return static::getRelations()[$is_model ? 0 :1][$outer ? 2 : 1];
	}

	protected static function relations() {
		throw new InvalidConfigException('relations() should be defined for ' . self::className());
		return null;
	}

	private static function getRelations() {
		$relations = static::relations();

		if (is_array($relations[0])) {
			return $relations;
		}
		$lang_model = ArrayHelper::getValue(\Yii::$app->params, 'lang_model', 'yii\admin\models\Lang');
		return [$relations, [$lang_model::className(), 'lang_id', 'id']];
	}

	private static function getSingleRelation($is_model = true) {
		$relations = static::getRelations();

		return $relations[$is_model ? 0 : 1];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getModel()
	{
		$relation = static::getSingleRelation(true);
		return $this->hasOne($relation[0], [$relation[2] => $relation[1]]);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getLang()
	{
		$relation = static::getSingleRelation(false);
		return $this->hasOne($relation[0], [$relation[2] => $relation[1]]);
	}

	public static function primaryKey() {
		$relations = static::getRelations();
		return [$relations[0][1], $relations[1][1]];
	}

	protected $_form_name;
	public function formName()
	{
		if (!$this->_form_name) {
			$model = $this->getModel()->modelClass;
			/* @var $model \yii\db\ActiveRecord */
			$model = new $model();
			$trans_field = null;

			foreach ($model->getBehaviors() as $beh) {
				if ($beh instanceof TranslateBehavior && $beh->model == self::className()) {
					$trans_field = $beh->attribute;
				}
			}
			if (empty($trans_field))
				throw new HttpException(500, 'TranslateBehavior is not found for ' . self::className());

			$this->_form_name = $model->formName() . '['.$trans_field.'][' . $this->getAttribute(static::getRelationField(false)) . ']';
		}
		return $this->_form_name;
	}
}

