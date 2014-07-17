<?php
/** Created by griga at 17.07.13 | 1:30.
 *
 * @var $this Controller
 */
?>
<!doctype html>
<html lang="ru-RU">
<head>
    <meta charset="UTF-8">
    <title></title>
</head>
<body>
<div class="container">
    <?php $this->widget('bootstrap.widgets.TbNavbar', array(
        //'brand' => CHtml::image(app()->home->getUrl('/images/logo.png'), app()->name, array('title'=>app()->name)),
        'brandUrl' => app()->home,
        'brand' => app()->name,
        'fixed' => false,
        'collapse' => true, // requires bootstrap-responsive.css
        'items' => array(
            array(
                'class' => 'bootstrap.widgets.TbMenu',
                'items' => array(
                    array('label' => 'Перевод', 'url' => array('/translation/module/index')),
                 ),
            ),
            array(
                'class' => 'bootstrap.widgets.TbMenu',
                'htmlOptions' => array('class' => 'pull-right'),
                'items' => array(
                    array('label' => 'Выход' . ' (' . Yii::app()->user->name . ')', 'url' => app()->getModule('user')->logoutUrl),
                ),
            ),
        ),
    )); ?>

</div>
<?php echo $content; ?>
</body>
</html>