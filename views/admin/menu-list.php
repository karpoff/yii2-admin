<?php

echo yii\grid\GridView::widget([
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