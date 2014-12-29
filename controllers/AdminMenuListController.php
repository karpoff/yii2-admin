<?php

namespace yii\admin\controllers;

use yii;

class AdminMenuListController extends yii\admin\components\AdminController
{
	/** @var \yii\admin\models\MenuItem $item */
	public $item;

	public function actionIndex() {
		$data = [];
		/** @var \yii\admin\models\MenuItem $child */
		foreach ($this->item->getChildren() as $child) {
			$data[] = [
				'href' => yii\helpers\Html::a($child->title(), $this->url('/'.$child->getAttribute('full_path')))
			];
		}

		$dataProvider = new yii\data\ArrayDataProvider(['allModels' => $data]);
		$dataProvider->setSort(false);
		$dataProvider->setPagination(false);

		return $this->render('/admin/menu-list', ['dataProvider' => $dataProvider]);
	}

}