<?php
namespace yii\admin\fields;

use kartik\select2\Select2;
use yii\helpers\Html;

class Relation extends ActiveField
{
	public $data;
	public $path;

	public function render($content = null)
	{
		$rel = $this->model->getRelation($this->attribute);
		$remote_field = '';
		$own_field = '';
		foreach ($rel->link as $remote_field => $own_field)
			break;

		if ($rel->multiple) {
			$content .= Html::activeLabel($this->model, $this->attribute, $this->labelOptions);
			$inputID = Html::getInputId($this->model, $this->attribute);

			$path = $this->path;
			$path .= (strpos($path, '?') !== false ? '&' : '?') . 'attributes[' . $remote_field . ']=' . $this->model->getAttribute($own_field);

			$content .= Html::tag('div', 'loading', ['id' => $inputID, 'class' => 'relation-content', 'data-url' => $path]);

			$view = $this->form->getView();
			$view->registerJs("$.yiiAdmin('loadChild', '{$inputID}');");
		} else {
			if ($this->data == null) {
				/* @var $related_model \yii\db\ActiveRecord */
				$related_model = \Yii::createObject($rel->modelClass);

				$data = [];
				foreach ($related_model::find()->all() as $item) {
					$data[$item->getAttribute($remote_field)] = (string) $item;
				}
			} else {
				$data = $this->data;
			}

			$content = (string) $this->form->field($this->model, $own_field)->widget(Select2::classname(), [
				'data' => $data,
				'pluginOptions' => [
					'allowClear' => true
				],
			]);
		}

		return parent::render($content);
	}
}