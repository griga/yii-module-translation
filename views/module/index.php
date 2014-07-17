<?php
/** Created by griga at 30.06.2014 | 17:03.
 *
 */

cs()->registerCoreScript('angular');
$this->module->registerScripts();
?>

<div class="row">
    <div class="col-sm-12" ng-app="TranslationApp" ng-controller="MainCtrl" id="translation-app">
        <translation-loader loading="loading"></translation-loader>
        <div class="message-wrapper">
            <div class="message" style="display: none">
            </div>
        </div>
        <h4><?= t('Translations') ?></h4>
        <ul class="nav nav-tabs">
            <li ng-repeat="category in categories" ng-class="{active: selectedCategory==category}"
                ><a ng-click="$parent.selectedCategory = category">{{category.name}}</a></li>
            <li ng-hide="loading" class="translation-filter">
                <input type="text" ng-model="searchValue" debounce="500" class="form-control">
                <i class="glyphicon glyphicon-search"></i>
                <i class="glyphicon glyphicon-remove" ng-show="searchValue" ng-click="searchValue=''"></i>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane" ng-repeat="category in categories" ng-class="{active: selectedCategory==category}">
                <table class="table table-hover table-striped table-bordered table-condensed grid-view">
                    <thead>
                    <tr>
                        <th ng-repeat="(langKey, language) in languages">{{language}}</th>
                        <th class="translation-actions"><i ng-click="addPhrase()"
                                    class="glyphicon glyphicon-plus"></i></th>
                    </tr>
                    <!--  new phrases  -->
                    <tr ng-repeat="phrase in category.newPhrases" class="success">
                        <td ng-repeat="translation in phrase.translations ">
                            <textarea ng-model="translation.value" class="form-control"></textarea>
                        </td>
                        <td class="translation-actions">
                            <i ng-click="savePhrase(phrase)" class="glyphicon glyphicon-ok"></i>
                            <i ng-click="cancelEdit(phrase)" class="glyphicon glyphicon-ban-circle"></i>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                        <!-- phrases -->
                        <tr ng-repeat="phrase in category.phrases| filter: searchValue |orderBy:-translations.en">
                            <td ng-repeat="translation in phrase.translations ">
                                <span ng-bind="translation.value" ng-hide="phrase.edit"> </span>
                                <textarea ng-model="translation.value" ng-show="phrase.edit" class="form-control"></textarea>
                            </td>
                            <td class="translation-actions">
                                <i ng-click="editPhrase(phrase)" ng-hide="phrase.edit" class="glyphicon glyphicon-pencil"></i>
                                <i ng-click="deletePhrase(phrase)" ng-hide="phrase.edit" class="glyphicon glyphicon-trash"></i>
                                <i ng-click="copyPhrase(phrase)" ng-hide="phrase.edit" class="glyphicon glyphicon-plus-sign"></i>
                                <i ng-click="savePhrase(phrase)" ng-show="phrase.edit" class="glyphicon glyphicon-ok"></i>
                                <i ng-click="cancelEdit(phrase)" ng-show="phrase.edit" class="glyphicon glyphicon-ban-circle"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    angular.module('TranslationApp', []).controller('MainCtrl', function ($scope, $http) {

        $scope.loading = true;
        $scope.languages = [];
        $scope.selectedCategory = undefined;
        $http.get('/admin/translation/module/all').then(function (response) {
            $scope.categories = response.data.categories;
            $scope.languages = response.data.languages;
            $scope.selectedCategory = response.data.categories.core;
            $scope.loading = false;
        });

        $scope.addPhrase = function(){
            if(!$scope.selectedCategory.newPhrases) $scope.selectedCategory.newPhrases = [];
            var phrase = {
                key:undefined,
                translations: [],
                isNew: true
            };
            angular.forEach($scope.languages, function(lang, key){
                phrase.translations.push({
                    key: key,
                    value: undefined
                });
            });
            $scope.selectedCategory.newPhrases.push(phrase);
        };
        $scope.editPhrase = function(phrase){
            phrase.backup = phrase.translations;
            phrase.edit = true;
        };
        $scope.copyPhrase = function(phrase){
            if(!$scope.selectedCategory.newPhrases) $scope.selectedCategory.newPhrases = [];
            var copy = {};
            angular.copy(phrase, copy);
            copy.isNew = true;
            $scope.selectedCategory.newPhrases.push(copy);
        };
        $scope.cancelEdit = function(phrase){

            if(phrase.isNew){
                var newPhrases = $scope.selectedCategory.newPhrases;
                newPhrases.splice(newPhrases.indexOf(phrase), 1);
            } else {
                phrase.translations = phrase.backup;
                phrase.edit = false;
            }
        };
        $scope.savePhrase = function(phrase){
            $scope.loading = true;
            phrase.edit = false;

            var data = {
                key: phrase.key,
                category: $scope.selectedCategory.name
            };

            angular.forEach(phrase.translations, function(translation){
                data[translation.key] = translation.value
            });

            $http.post('/admin/translation/module/save', {
                phrase: data
            }).then(function(response){
                $scope.loading = false;
                if(phrase.isNew){
                    phrase.key = phrase.translations.en;
                    $scope.selectedCategory.newPhrases.splice($scope.selectedCategory.newPhrases.indexOf(phrase), 1);
                    $scope.selectedCategory.phrases.push(phrase);
                    console.log(phrase);
                }
            });
        };
        $scope.deletePhrase = function(phrase){
            $scope.loading = true;
            $http.post('/admin/translation/module/delete', {
                phrase: {
                    key: phrase.key,
                    category: $scope.selectedCategory.name
                }
            }).then(function(response){
                $scope.selectedCategory.phrases.splice($scope.selectedCategory.phrases.indexOf(phrase), 1);
                $scope.loading = false;
            });
        };
    }).directive('translationLoader', function () {
        return {
            restrict: 'E',
            replace: true,
            template: '<div class="loader" ng-class="{loading: loading}"><div class="outer-circle"></div><div class="inner-circle"></div></div>',
            scope: {
                loading: '='
            },
            link: function (scope, element) {
                var parent = element.parent()[0];
                element.css({
                    left: (parent.offsetWidth - element[0].offsetWidth) / 2 + 'px',
                    top: (parent.offsetHeight - element[0].offsetHeight) / 2 + 'px'
                });
            }
        }
    }).directive('debounce', function($timeout) {
        return {
            restrict: 'A',
            require: 'ngModel',
            priority: 99,
            link: function(scope, elm, attr, ngModelCtrl) {
                if (attr.type === 'radio' || attr.type === 'checkbox') return;

                elm.off('input');

                var debounce;
                elm.on('input', function() {
                    $timeout.cancel(debounce);
                    debounce = $timeout( function() {
                        scope.$apply(function() {
                            ngModelCtrl.$setViewValue(elm.val());
                        });
                    }, attr.debounce || 1000);
                });
                elm.on('blur', function() {
                    scope.$apply(function() {
                        ngModelCtrl.$setViewValue(elm.val());
                    });
                });
            }

        }
    });

</script>