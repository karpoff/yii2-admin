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
	public $controller_params = [];
	public $models;

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

		if (!$this->models) {
			$tran_lng_field = $trans_model::getRelationField(false);
			$tran_mdl_field = $trans_model::getRelationField();
			$model_tran_field = $trans_model::getRelationField(true, true);

			$trans = ArrayHelper::index($trans_model::find()->where([$tran_mdl_field => $model->getAttribute($model_tran_field)])->all(), $tran_lng_field);
		} else {
			$trans = $this->models;
		}

		/* @var \yii\admin\controllers\ModelController $action */
		if ($this->controller)
			$controller = \Yii::createObject($this->controller, ['translation', \Yii::$app->controller->module, $this->controller_params, []]);
		else
			$controller = \Yii::createObject('yii\admin\controllers\ModelController', ['translation', \Yii::$app->controller->module, ArrayHelper::merge(['model_class' => $trans_model, 'child' => true], $this->controller_params), []]);

		$fs = $controller->getFormFields();
		$field_tabs = isset($fs['tabs']) ? $fs['tabs'] : $fs['fields'];

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
					$fbd = $tran->getPrimaryKey(true);
					if (isset($tran_mdl_field))
						$fbd[] = $tran_mdl_field;
					if (isset($tran_lng_field))
						$fbd[] = $tran_lng_field;
					foreach ($field_tabs as $fields) {
						foreach ($fields as $attr => $val) {
							if (array_search($attr, $fbd) === false && $tran[$attr] == $default[$attr]) {
								$tran->setAttribute($attr, '');
							}
						}
					}
				}
			}

			$out_tabs = [];
			foreach ($field_tabs as $tab_name => $fields) {
				$tab = ['label' => $tab_name, 'content' => ''];
				foreach ($fields as $name => $value) {
					$tab['content'] .= (string)(is_numeric($name)
						? $this->form->field($tran, $value)
						: $this->form->field($tran, $name, $value)
					);
				}
				$out_tabs[] = $tab;
			}
			switch (sizeof($out_tabs)) {
				case 1:
					$content = $out_tabs[0]['content'];
					break;
				default:
					$content = (string) Tabs::widget(['items' => $out_tabs]);
			}

			$tabs[] = ['label' => $lang->getAttribute('code'), 'content' => $content];
		}

		$content = Tabs::widget(['items' => $tabs]);
		return parent::render($content);
	}
}