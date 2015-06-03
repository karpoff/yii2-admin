<?php

namespace yii\admin\controllers;

use Yii;

class AdminMenuController extends ModelController
{
	/* @var $model \yii\admin\models\MenuItem */
	protected $model = 'yii\admin\models\MenuItem';

	public function init()
	{
		parent::init();
		$this->renderAjax = false;
	}
	public function editFields($options = [])
	{
		$fields = ['path', 'title', 'hidden' => ['checkbox']];

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
		return $this->actionEdit(null);
	}

	public function actionEdit($id)
	{
		/* @var $model \yii\admin\models\MenuItem */
		$model = ($id) ? $this->model->findOne($id) : $this->model;
		if (Yii::$app->request->getIsGet() && !Yii::$app->request->get('_form_') && ($model->getIsNewRecord() || (!$model->isItem()))) {
			$items = [];

			if (!$model->getIsNewRecord()) {
				$items[] = [
					'title' => '/',
					'id' => $model->id,
					'href' => $this->url('edit', ['id' => $model->id, '_form_' => true]),
					'_fixed' => 'true',
				];
			}

			/** @var \yii\admin\models\MenuItem $child */
			foreach ($model->getChildren() as $child) {
				$item = $child->attributes;
				$item['href'] = $this->url('edit', ['id' => $child->id]);
				$item['title'] = $child->title();
				$items[] = $item;
			}

			$items[] = [
				'title' => '+',
				'href' => $this->url('add', ['parent' => ($model->getIsNewRecord() ? '' : $model->id), '_form_' => true]),
				'_fixed' => 'true',
			];
			return $this->render('/admin/menu', [
				'items' => $items,
				'sort' => $this->url('sort'),
				'id' => $model->getIsNewRecord() ? 0 : $model->id
			]);
		}

		if ($model->getIsNewRecord()) {
			$parent = intval(Yii::$app->request->get('parent'));
			if ($parent)
				$this->model->parent = $parent;
		}
		$this->jsModelFormOptions = [
			'onSuccess' => "
				$('ul:first li[aria-controls=\"'+_form.closest('.ui-tabs-panel').attr('id')+'\"]', _form.closest('.admin-menu')).trigger('form.updated', _frame.contents().find('body').html());
				alert('Saved');
			"
		];

		return parent::actionEdit($id);
	}

	public function returnEdit()
	{
		return json_encode([
			'id' => $this->model->id,
			'title' => $this->model->title(),
			'href' => $this->url('edit', ['id' => $this->model->id]),
		]);
	}

	public function actionDelete($id)
	{
		parent::actionDelete($id);
		return '<script>window.location.hash="'.$this->url('').'"; window.location.reload();</script>';
	}
	public function actionSort($id=null)
	{
		$this->model->findOne($id)->sort((int) Yii::$app->request->get('sort'));
	}
}
