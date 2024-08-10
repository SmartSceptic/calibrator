<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = Yii::t('app', 'Login').' — '.Yii::t('app', 'Brain Calibrator');

?>
<div class="site-login">
    <div class="jumbotron">
        <h1><?= Yii::t('app', 'Brain Calibrator') ?></h1>
        <br>
        <p>
            <?= Html::a(Yii::t('app', 'Login with Google'), ['site/auth', 'authclient' => 'google'], ['class' => 'btn btn-lg btn-primary']) ?>
        </p>
    </div>
</div>

