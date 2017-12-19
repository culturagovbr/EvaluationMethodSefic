(function (angular) {
    "use strict";

    var module = angular.module('opportunity.controller.categories', ['ngSanitize']);
    
    module.controller('CategoriesController',['$scope', 'EditBox', function($scope, EditBox){
        $scope.editBox = EditBox;
        $scope.tipologias = MapasCulturais.segmentos;
        $scope.tipologiaAtuacao = [
            {
                id: 0,
                _areas: $scope.tipologias,
                _segmentos: []
            }
        ];

        $scope.adicionarSegmento = function() {
            var novoSegmento = $scope.tipologiaAtuacao.length+1;
            $scope.tipologiaAtuacao.push(
                {
                    'id': novoSegmento,
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

            $scope.data._tipo2 = '';
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