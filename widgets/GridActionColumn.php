<?php
namespace yii\admin\widgets;

use yii\grid\Column;
use yii\helpers\Html;

class GridActionColumn extends Column
{
	public $actions = [];
	public $contentOptions = ['class' => 'grid-action-column'];

	protected function renderDataCellContent($model, $key, $index)
	{
		$out = '';
		foreach ($this->actions as $name => $action) {
			if (empty($action['condition']) || $action['condition']($model)) {
				//$options = ['data-pjax' => 0];
				$options = isset($action['options']) ? $action['options'] : [];
				$action['url'] = $this->prepareUrl($action['url'], $model);

				if (isset($action['popup'])) {
					$options['data-popup'] = $action['url'];
				}
				if (isset($action['confirm'])) {
					$options['data-confirm'] = $action['confirm'];
				}

				if (isset($action['icon'])) {
					$label = ' ';
					$options['class'] = 'glyphicon glyphicon-' . $action['icon'];
				} else {
					$label = $action['label'];
					$options['class'] = '';
				}
				$options['class'] .= ' action-'.$name;

				$out .= Html::a($label, $action['url'], $options);
			}
		}
		return $out;
	}

	protected function renderHeaderCellContent()
	{
		$view = $this->grid->getView();
		$view->registerJs("$.yiiAdmin('listActions', '{$this->grid->options['id']}');");
		return $this->grid->emptyCell;
	}

	protected function prepareUrl($url, $model)
	{
		return str_replace('__primary_key__', $model->primaryKey, $url);
	}
}