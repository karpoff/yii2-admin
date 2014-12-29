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
				$model_fk = array_keys($rel_model->link)[0];

				return $this->hasOne($beh->model, [$rel_model->link[$model_fk] => $model_fk])->where([array_values($model->getLang()->link)[0] => \Yii::$app->controller->module->getLanguageId()]);
			}
		}
		throw new InvalidConfigException('translate behavior is not properly configured');
	}
}