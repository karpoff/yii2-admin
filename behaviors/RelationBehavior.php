<?php
namespace yii\admin\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * RelationBehavior automatically saves model relations.
 *
 * To use RelationBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * use yii\admin\behaviors\RelationBehavior;
 *
 * function behaviors()
 * {
 *     return [
 *         [
 *             'class' => RelationBehavior::className(),
 *             'relation' => 'text',
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Anton Karpov <karpoff@bk.ru>
 */
class RelationBehavior extends Behavior
{
	public $relation;
	public $scenarios = [];

	public $attribute;

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
			BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
			BaseActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
		];
	}

	/**
	 * This method is called at the end of inserting or updating a record.
	 */
	public function afterSave()
	{
		/** @var BaseActiveRecord $model */
		$model = $this->owner;

		if (!in_array($model->scenario, $this->scenarios))
			return;

		$added = $model->{$this->attribute};

		if (!$model->isNewRecord)
			$model->unlinkAll($this->relation, true);

		if (is_array($added)) {

			$relation = $model->getRelation($this->relation);

			/** @var \yii\db\ActiveRecord $remote_model */
			$remote_model = $relation->modelClass;

			foreach ($added as $add) {
				$model->link($this->relation, $remote_model::findOne($add));
			}
		}
	}

	/**
	 * This method is called before deleting record.
	 */
	public function beforeDelete()
	{
		/** @var BaseActiveRecord $model */
		$model = $this->owner;
		$model->unlinkAll($this->relation, true);
	}
}