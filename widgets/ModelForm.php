<?php
namespace yii\admin\widgets;

use yii\base\InvalidConfigException;
use yii\bootstrap\Nav;
use yii\bootstrap\Tabs;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

class ModelForm extends ActiveForm
{
	/* @var $model \yii\db\ActiveRecord */
	public $model;
	public $fields = [];

	public $return_url;
	public $actions;

	public $onSuccess;
	public $tabs;

	public $defaultClassPath;
	public $showSubmitButton = true;
	public $modal = false;

	public function init()
	{
		$this->validateOnSubmit = true;
		$this->enableClientValidation = false;
		$this->enableAjaxValidation = true;
		$this->enableClientScript = true;
		$this->validateOnChange = false;
		$this->validateOnType = false;
		$this->validateOnBlur = false;

		if (empty($this->validationUrl)) {
			$this->validationUrl = Url::to($this->action) . '&validate';
		}

		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}

		$this->options['method'] = 'post';
		$this->options['encoding'] = 'multipart/form-data';
		$this->options['enctype'] = 'multipart/form-data';

		if ($this->modal) {
			$this->options['target'] = $this->options['id'] . '_frame';
		}
		echo Html::beginForm($this->action, $this->method, $this->options);

		if ($this->actions) {
			echo Nav::widget([
				'options' => ['class' => 'navbar-nav navbar-right'],
				'items' => [
					[
						'label' => 'Actions',
						'items' => $this->actions
					]
				],
			]);
		}
		if ($this->showSubmitButton) {
			echo Html::submitButton($this->model->isNewRecord ? 'Add' : 'Save', ['class' => 'btn btn-default', 'style' => 'float: right;margin-top:5px;margin-right: 20px;']);
			echo Html::tag('div', '', ['style' => 'clear:both;']);
		}
	}

	public function getId($autoGenerate = true)
	{
		$t = $this->model;
		$t = strtolower($t::className());
		$t = explode('\\', $t);
		$t = $t[sizeof($t) - 1];
		return $t . '-form';
	}

	public function run()
	{
		if ($this->tabs) {
			$tabs = $this->tabs;
		} else if ($this->fields) {
			$tabs = [$this->fields];
		} else {
			throw new InvalidConfigException('No fields are configured for model');
		}

		$out_tabs = [];
		foreach ($tabs as $tab_name => $fields) {
			if (is_string($fields)) {
				$fields = explode(',', $fields);
				foreach ($fields as $ind => $field)
					$fields[$ind] = trim($field);
			}
			$tab = ['label' => $tab_name, 'content' => ''];

			foreach ($fields as $field_name => $field) {
				$tab['content'] .= (string)(is_numeric($field_name)
					? $this->field($this->model, $field)
					: $this->field($this->model, $field_name, $field)
				);
			}
			$out_tabs[] = $tab;
		}

		switch (sizeof($out_tabs)) {
			case 0:
				throw new InvalidConfigException('No fields are configured for model');
			case 1:
				echo $out_tabs[0]['content'];
				break;
			default:
				echo Tabs::widget(['items' => $out_tabs]);
		}

		$view = $this->getView();
		$view->registerJs("$.yiiAdmin('initForm', '{$this->options['id']}')");
		parent::run();

		if ($this->modal) {
			echo Html::tag('iframe', '', [
				'id' => $this->options['id'] . '_frame',
				'name' => $this->options['id'] . '_frame',
				'style' => 'display: none',
			]);
		}
	}

	public function field($model, $attribute, $options = [])
	{
		if (isset($options['widget'])) {
			$widget = $options['widget'];
			unset($options['widget']);
			return parent::field($model, $attribute)->widget($widget, $options);
		}
		if (isset($options['class']) && $this->defaultClassPath && strpos($options['class'], '\\') === false)
			$options['class'] = $this->defaultClassPath . '\\' . $options['class'];
		return parent::field($model, $attribute, $options);
	}
}