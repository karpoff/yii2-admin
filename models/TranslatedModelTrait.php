<?php

namespace yii\admin\models;

use yii\admin\behaviors\TranslateBehavior;
use yii\base\InvalidConfigException;

trait TranslatedModelTrait
{
	public function getTrans() {
		/** @var \yii\db\ActiveRecord $this */
		foreach ($this->getBehaviors() as $beh) {
			if ($beh instanceof TranslateBehavior) {
				$model = $beh->model;
				$model = new $model();
				if (!($model instanceof Translation))
					continue;

				$rel_model = $model->getModel();
				$trans_model = $model->getLang();
				/** @var \yii\admin\models\LangInterface $trans_model_class */
				$trans_model_class = $trans_model->modelClass;
				$model_fk = array_keys($rel_model->link)[0];

				return $this->hasOne($beh->model, [$rel_model->link[$model_fk] => $model_fk])->where([array_values($trans_model->link)[0] => $trans_model_class::getCurrentId()]);
			}
		}
		throw new InvalidConfigException('translate behavior is not properly configured');
	}
}