<?php
use yii\admin\assets\PageAsset;
use yii\admin\YiiAdminModule;
use yii\widgets\Breadcrumbs;

PageAsset::register($this);

/* @var $this \yii\web\View */
$this->beginPage();
$this->head();
$this->beginBody();

/* @var \yii\admin\YiiAdminModule $module */
$module = YiiAdminModule::getInstance();

if (empty($this->title))
	$this->title = $module->getMenuItemTitle();

//echo $module->getBreadcrumbs($this->title);
echo $content;

$this->endBody();
$this->endPage();