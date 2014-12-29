<?php
use yii\grid\GridView;

/*echo \yii\bootstrap\Button::widget(['options' => ['id' => 'button-add', 'href' => $add, 'class' => 'btn btn-primary', 'style' => 'float: right;'], 'tagName' => 'a', 'label' => 'Add']);
echo \yii\helpers\Html::tag('div', '', ['class' => 'clear']);
\yii\widgets\Pjax::begin(['options' => ['id' => 'list-grid-pjax'], 'clientOptions' => ['push' => false, 'replace' => false, 'history' => false]]);
echo GridView::widget(array_merge(['id' => 'list-grid'], $grid_config));
\yii\widgets\Pjax::end();*/

echo $add;
echo \yii\helpers\Html::tag('div', '', ['class' => 'clear']);

//\yii\widgets\Pjax::begin(['options' => ['id' => $grid_config['id'] . '-pjax', 'class' => 'pjax-grid'], 'clientOptions' => ['push' => false, 'replace' => false, 'history' => false]]);
echo GridView::widget($grid_config);
//\yii\widgets\Pjax::end();