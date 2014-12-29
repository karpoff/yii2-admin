<?php

namespace yii\admin\actions;

use Yii;

class AdminMenuAction extends ViewerAction
{
	/* @var $model \yii\admin\models\MenuItem */
	protected $model = 'yii\admin\models\MenuItem';

	public function editFields($options = [])
	{
		$fields = ['path', 'title'];

		/* @var $model \yii\admin\models\MenuItem */
		$model = $this->model;

		if ($model->getIsNewRecord()) {
			$fields['type'] = ['dropDownList', [1 => 'Item', 0 => 'Category']];
		} else {
			if ($model->isItem()) {
				$fields[] = 'class';
			}
		}
		return $fields;
	}

	public function actionList()
	{
		return $this->actionEdit();
	}

	public function actionEdit()
	{
		/* @var $model \yii\admin\models\MenuItem */
		$model = $this->model;
		if (Yii::$app->request->getIsGet() && !Yii::$app->request->get('_form_') && ($model->getIsNewRecord() || (!$model->isItem()))) {
			$items = [];

			if (!$model->getIsNewRecord()) {
				$items[] = [
					'title' => '/',
					'id' => $model->id,
					'href' => $this->controller->url([['id' => $model->id, '_form_' => true], 'action' => 'edit']),
					'_fixed' => 'true',
				];
			}

			/** @var \yii\admin\models\MenuItem $child */
			foreach ($this->model->getChildren() as $child) {
				$item = $child->attributes;
				$item['href'] = $this->controller->url([['id' => $child->id], 'action' => 'edit']);
				$item['title'] = $child->title();
				$items[] = $item;
			}

			$items[] = [
				'title' => '+',
				'href' => $this->controller->url([['parent' => ($model->getIsNewRecord() ? '' : $model->id), '_form_' => true], 'action' => 'edit']),
				'_fixed' => 'true',
			];
			return $this->controller->render('menu', [
				'items' => $items,
				'sort' => $this->controller->url(['action' => 'sort']),
				'id' => $model->getIsNewRecord() ? 0 : $model->id
			]);
		}

		if ($model->getIsNewRecord()) {
			$parent = intval(Yii::$app->request->get('parent'));
			if ($parent)
				$model->parent = $parent;
		}
		$this->jsModelFormOptions = [
			'onSuccess' => "
				$('ul:first li[aria-controls=\"'+_form.closest('.ui-tabs-panel').attr('id')+'\"]', _form.closest('.admin-menu')).trigger('form.updated', _frame.contents().find('body').html());
				adminSuccessMessage(_form, 'Saved');
			"
		];
		return parent::actionEdit();
	}

	public function returnEdit()
	{
		return json_encode([
			'id' => $this->model->id,
			'title' => $this->model->title(),
			'href' => $this->controller->url([['id' => $this->model->id], 'action' => 'edit']),
		]);
	}

	public function actionDelete()
	{
		parent::actionDelete();
		return '<script>window.location.hash="'.$this->controller->url([], true).'"; window.location.reload();</script>';
	}
	public function actionSort()
	{
		$this->model->sort((int) Yii::$app->request->get('sort'));
	}
}
