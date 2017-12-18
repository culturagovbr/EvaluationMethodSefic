(function (angular) {
    "use strict";

    var module = angular.module('opportunity.controller.categories', ['ngSanitize']);
    
    module.controller('CategoriesController',['$scope', 'EditBox', function($scope, EditBox){
        $scope.editBox = EditBox;
        console.log(MapasCulturais.segmentos);
        
        $scope.segmentos = MapasCulturais.segmentos;

        // var n1 = MapasCulturais.entity.tipologia_nivel1;
        // var n2 = MapasCulturais.entity.tipologia_nivel2;
        // var n3 = MapasCulturais.entity.tipologia_nivel3;

        // types.__values = Object.keys(types);
        // types.__values.forEach(function(val){
        //     types[val].__values = Object.keys(types[val]);
        // });
        
        $scope.data = {
            // _tipo1: n1,
            // _tipo2: n2,
            // _tipo3: n3,
            
            // tipologia1: n1,
            // tipologia2: n2,
            // tipologia3: n3,
            
            // _types: types,
            // _valores_nivel1: types.__values,
            // _valores_nivel2: n1 ? types[n1].__values : [],
            // _valores_nivel3: n2 ? types[n1][n2] : [],
        };
        
        $scope.set = function(n){
            if(n === 1){
                $scope.data._valores_nivel2 = types[$scope.data._tipo1].__values;
                $scope.data._valores_nivel3 = [];
                
                $scope.data._tipo2 = '';
                $scope.data._tipo3 = '';
                
            } else if (n === 2) {
                $scope.data._valores_nivel3 = types[$scope.data._tipo1][$scope.data._tipo2];
                $scope.data._tipo3 = '';
            }
        };
        
        var setEditables = function(){
            $('#tipologia_nivel1').first().editable('setValue', $scope.data.tipologia1);
            $('#tipologia_nivel2').first().editable('setValue', $scope.data.tipologia2);
            $('#tipologia_nivel3').first().editable('setValue', $scope.data.tipologia3);  
        };
        
        setEditables();
        
        $scope.setTypes = function(){
            $scope.data.tipologia1 = $scope.data._tipo1;
            $scope.data.tipologia2 = $scope.data._tipo2;
            $scope.data.tipologia3 = $scope.data._tipo3;
            
            setEditables();
            
            $scope.data.tipologia = $scope.data._tipo3;
            EditBox.close('eb-tipologia');
        };
        
        
        $scope.resetValues = function(){
            $scope.data._tipo1 = $scope.data.tipologia1;
            $scope.data._tipo2 = $scope.data.tipologia2;
            $scope.data._tipo3 = $scope.data.tipologia3;
        };
        
    }]);
})(angular);