<?php
/** \yii\web\View $this */
use kartik\select2\Select2;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/*echo \yii\bootstrap\Button::widget(['options' => ['id' => 'button-add', 'href' => $add, 'class' => 'btn btn-primary', 'style' => 'float: right;'], 'tagName' => 'a', 'label' => 'Add']);
echo \yii\helpers\Html::tag('div', '', ['class' => 'clear']);
\yii\widgets\Pjax::begin(['options' => ['id' => 'list-grid-pjax'], 'clientOptions' => ['push' => false, 'replace' => false, 'history' => false]]);
echo GridView::widget(array_merge(['id' => 'list-grid'], $grid_config));
\yii\widgets\Pjax::end();*/

if (!empty($add))
	echo $add;


if (!empty($relation_filter)) {
	echo Html::beginTag('div', ['style' => 'width:50%;']);
	echo Select2::widget([
		'id' => 'relation_select',
		'data' => $relation_filter['data'],
		'name' => 'relation',
		'value' => $relation_filter['current'],
		'options' => [
			'multiple' => false,
			'style' => 'width:100%;'
		]
	]);
	echo Html::endTag('div');

	$this->registerJs("$('#relation_select').change(function () {
		window.location.href = '{$relation_filter['url']}' + $(this).val();
	});");
}


echo \yii\helpers\Html::tag('div', '', ['class' => 'clear']);

//\yii\widgets\Pjax::begin(['options' => ['id' => $grid_config['id'] . '-pjax', 'class' => 'pjax-grid'], 'clientOptions' => ['push' => false, 'replace' => false, 'history' => false]]);
echo GridView::widget($grid_config);
//\yii\widgets\Pjax::end();

if (!empty($sortable_url)) {
	?>
<script type="text/javascript">
	$(document).ready(function() {
		var fixHelper = function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		};
		var rows_body = $("#<?=$grid_config['id']?> table:first tbody:first");
		if ($('> tr', rows_body).size() > 1) {
			rows_body.sortable({
				axis: "y",
				helper: fixHelper,
				stop: function (e, ui) {
					var order = [];
					$("#<?=$grid_config['id']?> table:first tbody:first > tr").each(function() {
						order.push($(this).attr('data-key'));
					});
					$('#loader').show();
					$.get('<?=$sortable_url?>', {order: order})
						.always(function() {
							$('#loader').hide();
						}).error(function() {
							alert('error while sort update');
							window.location.reload();
						});
				}
			}).disableSelection();
		}
	});
</script>
<?
}