<?php

namespace yii\admin\actions;

use yii;
use yii\admin\behaviors\TranslateBehavior;
use yii\admin\widgets\ModelForm;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\di\Instance;

class AdminMenuListAction extends AdminAction
{
	/** @var \yii\admin\models\MenuItem $item */
	public $item;

	public function actionGet() {
		$data = [];
		/** @var \yii\admin\models\MenuItem $child */
		foreach ($this->item->getChildren() as $child) {
			$data[] = [
				'href' => yii\helpers\Html::a($child->title(), $this->controller->url(['path' => $child->getAttribute('full_path')], true))
			];
		}

		$dataProvider = new yii\data\ArrayDataProvider(['allModels' => $data]);
		$dataProvider->setSort(false);
		$dataProvider->setPagination(false);

		yii\widgets\Breadcrumbs::
		return yii\grid\GridView::widget([
			'options' => ['class' => 'grid-view container'],
			'dataProvider' => $dataProvider,
			'showHeader' => false,
			'layout' => '{items}',
			'columns' => [
				[
					'attribute' => 'href',
					'format' => 'raw'
				]
			]
		]);
	}

}