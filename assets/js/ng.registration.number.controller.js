(function (angular) {
    "use strict";

    var module = angular.module('registration.number.controller', ['ngSanitize']);

    module.controller('RegistrationNumberController',['$scope',  function($scope){
        $scope.$watch('data.evaluations', function(){
            angular.forEach($scope.data.evaluations, (e) => {
                e.registration.number = "on-" + e.registration.previousPhaseRegistrationId
            })
        });

        $scope.$watch('data.registrations', function(){
            angular.forEach($scope.data.registrations, (e) => {
                e.number = "on-" + e.previousPhaseRegistrationId;
                // e.singleUrl = e.singleUrl.replace(e.id, e.previousPhaseRegistrationId);
            });
        });
    }]);
})(angular);