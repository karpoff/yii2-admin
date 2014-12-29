<?php
use kartik\nav\NavX;
use yii\admin\assets\AdminAsset;
use yii\admin\YiiAdminModule;
use yii\bootstrap\Modal;
use yii\bootstrap\NavBar;
use yii\bootstrap\Nav;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */

AdminAsset::register($this);
$this->beginPage();

$this->registerJs("$.yiiAdmin();");

/* @var \yii\admin\YiiAdminModule $module */
$module = YiiAdminModule::getInstance();

if (empty($this->title))
	$this->title = $module->getMenuItemTitle();

?>

<!DOCTYPE html>
<head lang="ru">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta charset="<?= Yii::$app->charset ?>"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?= Html::csrfMetaTags() ?>
	<title><?= Html::encode($this->title) ?></title>
	<?php $this->head() ?>
</head>

<body>
<?php $this->beginBody() ?>

<?php

$url = '/' . $module->id;

NavBar::begin([
	'id' => 'admin-nav-bar',
	'brandLabel' => 'Admin',
	'brandUrl' => $url,
	'options' => [
		'class' => 'navbar-inverse',
	],
]);

echo NavX::widget([
	'id' => 'admin-menu',
	'options'=>['class'=>'nav navbar-nav'],
	'items' => $module->getMenu()
]);

$right_menu = [];

$languages = $module->getLanguages('admin');

if ($languages) {

	$language = Yii::$app->language;

	$l = ['encode' => false, 'items' => [], 'options' => ['id' => 'admin-menu-language', 'data-href' => Url::toRoute('admin-user/language')]];

	/* @var $lang \yii\admin\models\LangInterface */
	foreach ($languages as $lang) {
		$label = '<div class="flag flag-'.$lang->getCode().'"> </div>';

		if ($lang->getCode() == $language) {
			$l['label'] = $label;
		} else {
			$l['items'][] = [
				'label' => $label . $lang->getTitle(),
				'url' => '#',
				'encode' => false,
				'linkOptions' => ['data-language' => $lang->getCode()],
			];
		}
	}
	$right_menu[] = $l;
}

if (true) {
	$right_menu[] = [
		'label' => '',
		'linkOptions' => ['class' => 'glyphicon glyphicon-plus'],
		'items' => [
			['label' => Yii::t('yii.admin', 'Menu'), 'url' => $url . '/admin-menu'],
			['label' => Yii::t('yii.admin', 'Languages'), 'url' => $url . '/admin-lang'],
			//['label' => 'Пользователи', 'url' => $url . '/admin-users'],
		]
	];
}

$right_menu[] = [
	'label' => '',
	'linkOptions' => ['class' => 'glyphicon glyphicon-user'],
	'items' => [
		['label' => Yii::t('yii.admin', 'Logout'), 'url' => $url . '/admin-user/logout', ],
	]
];
//'linkOptions' => ['data-direct' => true]
echo Nav::widget([
	'id' => 'admin-menu-settings',
	'options' => ['class' => 'navbar-nav navbar-right'],
	'items' => $right_menu,
]);
NavBar::end();
?>

<div id="content" class="container"><?php echo $module->getBreadcrumbs($this->title); echo $content; ?></div>

<div class="clear"></div>

<?php
Modal::begin([
	'header' => '<h4 class="modal-title"></h4>',
	'id' => 'modal-form',
	'footer' => '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button><button type="button" class="btn btn-primary">Save changes</button>',
	'size' => Modal::SIZE_LARGE,
]);

Modal::end();
?>
<div id='loader'><div></div></div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
