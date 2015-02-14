<?php
namespace yii\admin\fields;

use yii\admin\behaviors\TranslateBehavior;
use yii\admin\models\Lang;
use yii\admin\YiiAdminModule;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;

class Translation extends ActiveField
{
	public $fields;
	public $controller;

	public function render($content = null)
	{
		$model = $this->model;

		/* @var $behavior \yii\admin\behaviors\TranslateBehavior */
		$behavior = null;
		foreach ($model->getBehaviors() as $beh) {
			if (!empty($beh->attribute) && $beh->attribute == $this->attribute) {
				if ($beh instanceof TranslateBehavior) {
					$behavior = $beh;
					break;
				}
			}
		}

		$tabs = [];

		/* @var $trans_model \yii\admin\models\Translation */
		$trans_model = $behavior->model;

		$tran_lng_field = $trans_model::getRelationField(false);
		$tran_mdl_field = $trans_model::getRelationField();
		$model_tran_field = $trans_model::getRelationField(true, true);

		$trans = ArrayHelper::index($trans_model::find()->where([$tran_mdl_field => $model->getAttribute($model_tran_field)])->all(), $tran_lng_field);

		/* @var \yii\admin\controllers\ModelController $action */
		if ($this->controller)
			$controller = \Yii::createObject($this->controller, ['translation', \Yii::$app->controller->module]);
		else
			$controller = \Yii::createObject('yii\admin\controllers\ModelController', ['translation', \Yii::$app->controller->module, ['model_class' => $trans_model, 'child' => true], []]);

		$fields = $controller->editFields();
		if ($controller->processEditFields)
			$fields = $controller->processEditFields($fields);

		$default = null;

		/* @var $lang \yii\admin\models\Lang */
		foreach (YiiAdminModule::getInstance()->getLanguages() as $lang) {

			$content = '';

			/* @var $tran \yii\admin\models\Translation */
			if (empty($trans[$lang->getPrimaryKey()])) {
				$tran = new $trans_model();
				$tran->loadDefaultValues();

				$tran->setAttribute($tran_lng_field, $lang->getPrimaryKey());
			} else {
				$tran = $trans[$lang->getPrimaryKey()];
			}

			if (!$default) {
				$default = $tran;
			} else {
				if (is_array($behavior->copyDefault)) {
					foreach ($behavior->copyDefault as $attr) {
						if ($tran[$attr] == $default[$attr])
							$tran[$attr] = '';
					}
				} else if ($behavior->copyDefault === true) {
					$fbd = $model->getPrimaryKey(true);
					$fbd[] = $tran_mdl_field;
					$fbd[] = $tran_lng_field;
					foreach ($tran as $attr => $val) {
						if (array_search($attr, $fbd) === false && $tran[$attr] == $default[$attr]) {
							$tran[$attr] = '';
						}
					}
				}
			}

			foreach ($fields as $name => $value) {
				$content .= (string) (is_numeric($name)
					? $this->form->field($tran, $value)
					: $this->form->field($tran, $name, $value)
				);
			}
			$tabs[] = ['label' => $lang->getAttribute('code'), 'content' => $content];
		}

		$content = Tabs::widget(['items' => $tabs]);
		return parent::render($content);
	}
}