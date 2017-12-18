(function (angular) {
    "use strict";

    var module = angular.module('opportunity.controller.categories', ['ngSanitize']);
    
    module.controller('CategoriesController',['$scope', 'EditBox', function($scope, EditBox){
        $scope.editBox = EditBox;
        // console.log(MapasCulturais.segmentos);
        
        $scope.segmentos = MapasCulturais.segmentos;
        

        // var n1 = MapasCulturais.entity.tipologia_nivel1;
        // var n2 = MapasCulturais.entity.tipologia_nivel2;
        // var n3 = MapasCulturais.entity.tipologia_nivel3;

        // types.__values = Object.keys(types);
        // types.__values.forEach(function(val){
        //     types[val].__values = Object.keys(types[val]);
        // });
        

        $scope.data = {
            // _tipo1: [],
            // _tipo2: [],
            // _tipo3: n3,
            
            // tipologia1: n1,
            // tipologia2: n2,
            // tipologia3: n3,
            
            // _types: types,
            // _valores_nivel1: types.__values,
            // _valores_nivel2: n1 ? types[n1].__values : [],
            // _valores_nivel3: n2 ? types[n1][n2] : [],
            _areas: $scope.segmentos,
            _segmentos: []
        };
        

        
        $scope.set = function(n){
            if(n === 1){
                $scope.data._segmentos = $scope.data._areas[$scope.data._tipo1];
                
                $scope.data._tipo2 = '';
            }
        };
        
        var setEditables = function(){
            $('#area').first().editable('setValue', $scope.data.area);
            $('#segmento').first().editable('setValue', $scope.data.segmento);
        };
        
        setEditables();
        
        $scope.setTypes = function(){
            $scope.data.area = $scope.data._tipo1;
            $scope.data.segmento = $scope.data._tipo2;
            
            setEditables();
            
            EditBox.close('eb-tipologia');
        };
        
        
        $scope.resetValues = function(){
            $scope.data._tipo1 = $scope.data.area;
            $scope.data._tipo2 = $scope.data.segmento;
        };
        
    }]);
})(angular);