<?php

namespace yii\admin\widgets;

use yii\admin\assets\MessageAsset;
use yii\bootstrap\Widget;
use yii\helpers\Html;
use yii\helpers\Json;


class MessageWidget extends Widget
{
	public $sources;
	public $languages;
	public $edit_source;

	public $options = ['class' => 'message-widget'];
	/**
	 * @inheritdoc
	 */
	public function run()
	{
		if (!empty($this->options['id']))
			$this->options['id'] = $this->getId();

		echo Html::tag('div', '', $this->options);
		$this->registerClientScript();
	}

	/**
	 * Registers the needed client script and options.
	 */
	public function registerClientScript()
	{
		$view = $this->getView();

		$config = [
			'sources' => $this->sources,
			'languages' => $this->languages,
			'edit_source' => $this->edit_source,
			'save_text' => \Yii::t('yii.admin', 'Save'),
		];

		$js = '$("#'.$this->options['id'].'").yiiMessages(' . Json::encode($config) . ');';
		MessageAsset::register($view);
		$view->registerJs($js);
	}
}
