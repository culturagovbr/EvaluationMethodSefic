(function (angular) {
    "use strict";

    var module = angular.module('registration.controller', ['ngSanitize']);

    module.controller('RegistrationController',['$scope', 'EditBox', '$http', function($scope, EditBox, $http){
        var labels = MapasCulturais.gettext.moduleOpportunity;

        function getOpportunityId(){
            if(MapasCulturais.request.controller == 'registration'){
                return MapasCulturais.entity.object.opportunity.id;
            } else {
                return MapasCulturais.entity.id;
            }
        }

        $scope.createOpportunityRegistration = function() {
            // it works
            var registration = {};
            registration['category'] = [];

            angular.forEach(jQuery('[class*="js-editable"]'), function(e){
                if(jQuery(e)[0].id == "category"){
                    registration['category'].push(jQuery(e)[0].textContent);
                }else{
                    registration[jQuery(e)[0].id] = jQuery(e)[0].textContent;
                }
            });

            registration['ownerId'] = MapasCulturais.entity.ownerId;
            registration['opportunityId'] = MapasCulturais.entity.object.opportunity.id;


            //here be dragons
            angular.forEach(registration['category'],function(v){
                registration.category = v;
                var data = Object.assign({}, registration);

                $http.post('/inscricoes/', data).success(function (data, status) {
                    MapasCulturais.Messages.success(labels['changesSaved']);
                }).error(function (data, status) {
                    MapasCulturais.Messages.error(labels['correctErrors']);
                });

            });

        };

    }]);
})(angular);