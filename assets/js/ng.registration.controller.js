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

        console.log(MapasCulturais);

        $scope.createOpportunityRegistration = function() {
            // it works
            var registration = {};
            angular.forEach(jQuery('[class*="js-editable"]'), function(e, i){
                registration[jQuery(e)[0].id] = jQuery(e)[0].textContent;
            });
            registration['ownerId'] = MapasCulturais.entity.ownerId;
            registration['opportunityId'] = MapasCulturais.entity.object.opportunity.id;

            // MapasCulturais.createUrl(MapasCulturais.request.controller, 'edit', MapasCulturais.request.id)

            $http.post('/inscricoes/'+MapasCulturais.request.id, registration).success(function (data, status) {
                MapasCulturais.Messages.success(labels['changesSaved']);
            }).error(function (data, status) {

            });
        };

    }]);
})(angular);