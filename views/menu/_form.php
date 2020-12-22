<?php

use afzalroq\cms\entities\ArticleCategories;
use afzalroq\cms\entities\Collections;
use afzalroq\cms\entities\Menu;
use afzalroq\cms\entities\Options;
use afzalroq\cms\entities\Pages;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this yii\web\View */
/* @var $model Menu */
/* @var $form yii\widgets\ActiveForm */

$options = Options::find()->all();
$optionByCollection = [];
/** @var Options $option */
foreach ($options as $option)
    $optionByCollection[Collections::optionUseInMenuList()[$option->collection->use_in_menu]][$option->id] = $option->slug;



?>

    <style type="text/css">
        .field-menu-action, .field-menu-alias, .field-menu-link, .field-menu-page_id {
            display: none;
        }
    </style>
<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div style="margin:5px 0 0 0;"
         class="alert alert-success"><?= Yii::$app->session->getFlash('success') ?></div><?php endif; ?>
    <div class="pages-form">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->errorSummary($model) ?>
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-tabs" role="tablist">
                    <?php foreach (Yii::$app->params['cms']['languages2'] as $key => $language) : ?>
                        <li role="presentation" <?= $key == 0 ? 'class="active"' : '' ?>>
                            <a href="#<?= $key ?>" aria-controls="<?= $key ?>" role="tab"
                               data-toggle="tab"><?= $language ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content">
                    <br>
                    <?php foreach (Yii::$app->params['cms']['languages2'] as $key => $language) : ?>
                        <div role="tabpanel" class="tab-pane <?= $key == 0 ? 'active' : '' ?>" id="<?= $key ?>">
                            <?php //= $model->showData($key); ?>

                            <?= $form->field($model, 'title_' . $key)->textInput(['maxlength' => true]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'sort')->dropDownList([1 => 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'type')->widget(Select2::class, [
                    'data' => ArrayHelper::map($options, 'id', 'slug'),
                    'options' => ['placeholder' => Yii::t('cms', 'Choose')],
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'action')->dropDownList($model->actionsList(), ['prompt' => Yii::t('cms', 'Choose') . ' ' . Yii::t('cms', 'action')]) ?>
                <?= $form->field($model, 'page_id')->dropDownList(ArrayHelper::map(Pages::find()->all(), 'id', 'title_0'), ['prompt' => Yii::t('cms', 'Choose')]) ?>
                <?= $form->field($model, 'articles_category_id')->dropDownList(ArrayHelper::map(ArticleCategories::find()->all(), 'id', 'title_0'), ['prompt' => Yii::t('cms', 'Choose')]) ?>
                <?= $form->field($model, 'link')->textInput(['placeholder' => 'http://']) ?>
            </div>
        </div>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('cms', 'Create') : Yii::t('cms', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
<?php
$constEmpty = Menu::TYPE_EMPTY;
$constAction = Menu::TYPE_ACTION;
$constLink = Menu::TYPE_LINK;
$constPage = Menu::TYPE_PAGE;
$constArticlesCategory = Menu::TYPE_ARTICLES_CATEGORY;

$ajaxUrl = Url::to(['menu/type']);

$script = <<< JS
    $(document).ready(function () {
        let action = $('.field-menu-action');
        let articlesCategoryId = $('.field-menu-articles_category_id');
        let link = $('.field-menu-link');
        let pageId = $('.field-menu-page_id');
        let type = $('#menu-type').val();
        typeControl(type);

        $('#menu-type').on('change', function () {
           
            typeControl(this.value);
        });


        function typeControl(type) {
            let t = 333;
            if (!type || type ==  $constEmpty) {
                hideAll();
            } else if (type == $constAction) {
                hideAll();
                action.slideDown(t);
            } else if (type == $constLink) {
                hideAll();
                link.slideDown();
            } else if (type == $constPage) {
                hideAll();
                pageId.slideDown(t);
            } else if (type == $constArticlesCategory) {
                hideAll();
                articlesCategoryId.slideDown(t);
            }
        }

        function hideAll() {
            action.hide();
            link.hide();
            pageId.hide();
            articlesCategoryId.hide();
        }
    });
JS;
$this->registerJs($script, View::POS_READY);
