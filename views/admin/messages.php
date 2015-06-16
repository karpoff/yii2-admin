<?php
/** @var $mode string  */

use yii\helpers\Html;

if ($mode == 'load') {
	echo Html::beginForm();

	echo '<div>';
	echo Html::label(Yii::t('yii.admin', 'Catalog with translation files'));
	echo '<br>';
	echo Html::input('text', 'path', '@app/messages');
	echo '</div>';

	echo '<div>';
	echo Html::dropDownList('mode', null, ['replace', 'update']);
	echo '</div>';
	echo '<div>';
	echo Html::submitButton();
	echo '</div>';
	echo Html::endForm();
}