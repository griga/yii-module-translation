<?php
/**
 * @var $module TranslationModule
 * @var $this DefaultController
 * */

$module = $this->module;
$module->registerScripts();
$this->id;

$this->breadcrumbs = array(
    t('Translations'),
);
?>
<div class="row">
<div class="col-sm-12">
<h4><?= t('Translations') ?></h4>
<ul class="nav nav-tabs">
    <?php foreach($module->translation->getTranslationData() as $category => $phrases):?>
        <li <?= $category=='core' ? 'class="active"' : '' ?>><a href="#<?= $category ?>" data-toggle="tab"><?= $category ?></a></li>
    <?php endforeach;?>
</ul>

<div class="tab-content">
    <?php

    foreach ($module->translation->getTranslationData() as $category => $phrases) {
        $appId = $category . 'TranslationApp';
        cs()->registerScript($category . 'AppData', 'window[\'' . $appId . 'Data\'] = ' . CJSON::encode($phrases), CClientScript::POS_END);
        cs()->registerScript($category . 'AppRun', 'ko.applyBindings(new TranslationApp("' . $category . '"),$(\'#' . $appId . '\')[0])');

        $content = CHtml::openTag('div',array(
            'id'=>$appId,
        ));

        /*---  message window  ---*/
        $content .= CHtml::tag('div',array('class'=>'message-wrapper'),CHtml::tag('div',array(
            'class'=>'message',
            'data-bind'=>'flash: message, css: state'
        ),''));
        /*---  / message  ---*/

        $content .= CHtml::openTag('table', array(
            'class' => 'table table-hover table-striped table-bordered table-condensed grid-view'
        ));

        /*---   header  ---*/
        $content .= CHtml::openTag('thead');
        /*---  labels  ---*/
        $content .= CHtml::openTag('tr');
        foreach ($module->languages as $shortCode => $label) {
            $content .= CHtml::tag('th', array(),$label);
        }
        $content .= CHtml::tag('th',array('class'=>'translation-actions'), CHtml::link('<i class="glyphicon glyphicon-plus"></i>', '#', array(
            'data-bind' => 'click: addPhrase'
        )));
        $content .= CHtml::closeTag('tr');
        /*---  /labels  ---*/

        /*---  filter  ---*/
        $content .= CHtml::openTag('tr', array(
            'class' => 'filters',
            'data-bind' => 'with: filter'
        ));
        foreach ($module->languages as $shortCode => $label) {
            $content .= CHtml::tag('td', array(), CHtml::tag('div', array('class' => 'filter-container'), CHtml::tag('input', array(
                'type' => 'text',
                'data-bind' => 'value: ' . $shortCode . ',valueUpdate: \'keyup\''
            ))));
        }
        $content .= CHtml::tag('td');
        $content .= CHtml::closeTag('tr');
        /*---  /filter  ---*/

        $content .= CHtml::closeTag('thead');
        /*---  /header ---*/

        /*---  body  ---*/
        $content .= CHtml::openTag('tbody', array(
            'data-bind' => 'template: {name: \'phrase-row\', foreach: filteredPhrases} '
        ));
        $content .= CHtml::closeTag('tbody');
        /*---  /body  ---*/

        $content .= CHtml::closeTag('table');

        $content .= CHtml::closeTag('div');
       /*---  /app wrapper  ---*/

        echo CHtml::tag('div', array(
            'class'=> $category=='core' ? 'tab-pane active' : 'tab-pane',
            'id'=>$category), $content);
    }

    ?>
</div>
</div>
</div>

<script type="text/javascript">
    ko.bindingHandlers.flash = {
        init: function(element) {
            $(element).hide();
        },
        update: function(element, valueAccessor) {
            var value = ko.utils.unwrapObservable(valueAccessor());
            if (value) {
                $(element).stop().hide().html(value).fadeIn(function() {
                    clearTimeout($(element).data("timeout"));
                    $(element).data("timeout", setTimeout(function() {
                        $(element).fadeOut();
                        valueAccessor()(null);
                    }, 3000));
                });
            }
        },
        timeout: null
    };

    var Languages = <?php echo CJSON::encode(array_keys($module->languages)) ;?>;
    var Phrase = function (data) {
        var self = this;
        self.key = null;
        self.category = null;
        self.backup = data;
        ko.utils.arrayForEach(Languages, function (language) {
            self[language] = ko.observable();
        });
        self.init(data);
        self.underEdit = ko.observable(false);
    };
    ko.utils.extend(Phrase.prototype, {
        getData: function(){
            var self = this,
                data = {
                    category: this.category,
                    key: this.key
                };
            ko.utils.arrayForEach(Languages, function (lang) {
                data[lang] = ko.utils.unwrapObservable(self[lang]);
            });
            return data;
        },
        editPhrase: function () {
            this.underEdit(true)
        },
        deletePhrase: function (phrase, event) {
            if(confirm('Точно удалять?')){
                $.ajax({
                    'url': '<?= $module->baseUrl?>/delete',
                    'type': 'post',
                    'data': { phrase: phrase.getData() },
                'success' : function(json){
                        $(event.target).closest('tr').fadeOut('normal',function(){
                            $(this).remove();
                            ko.contextFor(event.target).$root.phrases.remove(phrase);
                        })
                        ko.contextFor(event.target).$root.processMessage(json)
                },
                    'dataType':'json'
                })
            }
        },
        savePhrase: function (phrase, event) {
            var data = phrase.getData();
            $.ajax({
                type: 'post',
                url: '<?= $module->baseUrl?>/save',
                data: {phrase: data},
                success: function(json){
                    if (json.success){
                        phrase.underEdit(false);
                        phrase.backup = data;
                    }
                    ko.contextFor(event.target).$root.processMessage(json)
                },
                dataType: 'json'
            })
        },
        cancelPhrase: function () {
            this.underEdit(false);
            this.revert();
        },
        revert: function () {
            this.init(this.backup);
        },
        init: function (data) {
            data = data || {};
            var self = this;
            self.key = data.key;
            ko.utils.arrayForEach(Languages, function (language) {
                self[language](data[language]);
            });
            self.category = data.category;
            self.backup = data;
        }
    });

    var Filter = function () {
        var self = this;
        ko.utils.arrayForEach(Languages, function (language) {
            self[language] = ko.observable('')
        });
        self.isEmpty = ko.computed(self.isFilterEmpty, self);
    };
    ko.utils.extend(Filter.prototype, {
        isFilterEmpty: function () {
            var self = this, result = false;
            ko.utils.arrayForEach(Languages, function (lang) {
                result = result || self[lang]();
            })
            return !result
        },
        check: function (phrase) {
            var match = false, self = this;
            ko.utils.arrayForEach(Languages, function (lang) {
                var filter = ko.utils.unwrapObservable(self[lang]);
                if (filter)
                    match = match || ko.utils.unwrapObservable(phrase[lang]).indexOf(filter) === 0;
            });
            return match;
        }
    });
    var TranslationApp = function (category) {
        var self = this;
        self.addPhrase = function(){
            var phrase = new Phrase();
            phrase.underEdit(true);
            phrase.category = self.category;
            self.phrases.unshift(phrase);
        };
        self.category = category;
        self.phrases = ko.observableArray();
        self.filter = new Filter();
        self.message = ko.observable();
        self.state = ko.observable('success');

        self.processMessage = function(data){
            if (data.success){
                self.message(data.success);
                self.state('success')
            } else {
                self.message(data.error);
                self.state('error')
            }
        }
        self.filteredPhrases = ko.computed(function () {
            if (this.filter.isEmpty()) {
                return this.phrases();
            } else {
                return ko.utils.arrayFilter(this.phrases(), function (phrase) {
                    return self.filter.check(phrase);
                });
            }
        }, self);

        self.init = function () {
            var categoryData = window[category + 'TranslationAppData'];
            for (var phrase in categoryData) {
                var data = new Phrase({
                    'key': phrase,
                    'category': category
                });
                ko.utils.arrayForEach(Languages, function (lang) {
                    data[lang] = categoryData[phrase][lang];
                })
                self.phrases.push(new Phrase(data))
            }
        }


        self.init();
        window[category.replace(/\W/, '') + 'App'] = self;
    }
</script>
<script type="text/template" id="phrase-row"><?php
    echo CHtml::openTag('tr');
    foreach ($module->languages as $shortCode => $label) {
        echo CHtml::tag('td', array(),
            CHtml::tag('span', array(
                'data-bind' => 'html: ' . $shortCode . ', visible: !underEdit()',
            )) .
            CHtml::tag('input', array(
                'style' => 'margin: 0; width: 95%',
                'type' => 'text',
                'data-bind' => 'value: ' . $shortCode . ', visible: underEdit()',
            ))
        );
    }
    echo CHtml::tag('td', array('style' => 'width: 30px'),
        CHtml::link('<i class="glyphicon glyphicon-pencil"></i>', '#', array(
            'data-bind' => 'click: editPhrase, visible: !underEdit()'
        )) .
        CHtml::link('<i class="glyphicon glyphicon-trash"></i>', '#', array(
            'data-bind' => 'click: deletePhrase, visible: !underEdit()'
        )) .
        CHtml::link('<i class="glyphicon glyphicon-ok"></i>', '#', array(
            'data-bind' => 'click: savePhrase, visible: underEdit()'
        )) .
        CHtml::link('<i class="glyphicon glyphicon-ban-circle"></i>', '#', array(
            'data-bind' => 'click: cancelPhrase, visible: underEdit()'
        ))
    );
    echo CHtml::closeTag('tr');
    ?></script>


