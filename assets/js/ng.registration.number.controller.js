(function (angular) {
    "use strict";

    var module = angular.module('registration.number.controller', ['ngSanitize']);

    module.controller('RegistrationNumberController',['$scope', '$q',  function($scope, $q){
        var segmentos = MapasCulturais.segmentos;

        function getCategoryName(reg){
                return $q((resolve) => {
                    angular.forEach(segmentos, (val) => {
                        if (typeof val[reg.category] != 'undefined') {
                            resolve(val[reg.category]);
                        }
                    });
                });
        };

        $scope.$watch('data.registrations', function(){
            angular.forEach($scope.data.registrations, (e) => {
                getCategoryName(e).then((v) => {
                    e.categoryName = v;
                });
            });
        });
    }]);
})(angular);