(function (angular) {
    "use strict";

    var module = angular.module('opportunity.controller.categories', ['ngSanitize']);
    
    module.controller('CategoriesController',['$scope', 'EditBox', function($scope, EditBox){
        $scope.editBox = EditBox;
        $scope.tipologias = MapasCulturais.segmentos;
        $scope.tipologiaAtuacao = [
            {
                _areas: $scope.tipologias,
                _segmentos: [],
            }
        ];

        if(MapasCulturais.entity.object.category){
            $scope.tipologiaAtuacao = [];

            angular.forEach(MapasCulturais.entity.object.category.split(";"), (category)=> {
                $scope.tipologiaAtuacao.push(
                    {
                        _areas: $scope.tipologias,
                        _segmentos: [],
                        segmento: category
                    }
                );
            });
        }

        $scope.adicionarSegmento = function() {
            $scope.tipologiaAtuacao.push(
                {
                    // 'id': novoSegmento,
                    _areas: $scope.tipologias,
                    _segmentos: []
                }
            );
        };

        $scope.removerSegmento = function() {
            var ultimoSegmento = $scope.tipologiaAtuacao.length-1;
            $scope.tipologiaAtuacao.splice(ultimoSegmento);
        };

        $scope.set = function(index){
            $scope.tipologiaAtuacao[index]._segmentos = $scope.tipologias[$scope.tipologiaAtuacao[index].area];
        };
        
        $scope.setTypes = function(){

            var $box = jQuery('[id^=eb-tipologia]').find('>div.edit-box');
            $box.hide();
            jQuery('[id^=eb-tipologia]').trigger('close');
        };

        $scope.resetValues = function(){

        };

    }]);
})(angular);