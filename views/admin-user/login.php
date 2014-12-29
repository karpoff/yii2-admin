<?php
use yii\admin\assets\AdminAsset;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/* @var $this \yii\web\View */

AdminAsset::register($this);
$this->beginPage()
?>

<!DOCTYPE html>
<head lang="ru">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php $this->head() ?>
</head>

<body>
<?php $this->beginBody() ?>

<div class="container">
	<div class="login">
		<div class="container">
			<div id="loginbox" style="margin-top:50px" class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
				<div class="panel panel-info">
					<div class="panel-heading">
						<div class="panel-title">Administration Login</div>
					</div>
					<div style="padding-top:5px" class="panel-body">
					<?php $form = ActiveForm::begin([
						'id' => 'login-form',
						'options' => ['class' => 'form-horizontal'],
						'enableClientValidation'=> false,
						'enableAjaxValidation'=> true,
						'validateOnSubmit' => true,
						'validateOnChange' => false,
						'validateOnBlur' => false,
						'action' => ['admin-user/login'],
						'validationUrl' => ['admin-user/login', 'validate' => true],
						'fieldConfig' => [
							'options' => [
								'class' => 'input-group',
								'style' => 'margin-top: 25px;'
							],
							'errorOptions' => [
								'style' => 'position: absolute;top: 30px;',
								'class' => 'help-block'
							]
						]
					]);?>

						<?php echo $form->field($model, 'username', [
							'template' => '<span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>{input}{error}',
							'inputOptions' => ['class' => 'form-control', 'placeholder' => 'username'],
						]); ?>

						<?php echo $form->field($model, 'password', [
							'template' => '<span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>{input}{error}',
							'inputOptions' => ['class' => 'form-control', 'placeholder' => 'password'],
						])->passwordInput(); ?>

						<div style="margin-top:10px" class="form-group">
							<div class="col-sm-12 controls"><input style="float:right;"	value="Login" type="submit" id="login" class="btn btn-success"></div>
						</div>
					<?php ActiveForm::end(); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
