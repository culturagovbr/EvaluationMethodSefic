(function (angular) {
    "use strict";

    var module = angular.module('registration.controller', ['ngSanitize']);

    module.controller('RegistrationController',['$scope', 'EditBox', '$http', 'RegistrationService',  function($scope, EditBox, $http, RegistrationService){
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
            registration['category'] = registration['category'].join(';');

            $http.post('/inscricoes/single/'+MapasCulturais.entity.id, registration).success(function (data, status) {
                MapasCulturais.Messages.success(labels['changesSaved']);
            }).error(function (data, status) {
                MapasCulturais.Messages.error(labels['correctErrors']);
            });

        };

        $scope.sendRegistration = function(){
            $scope.createOpportunityRegistration();
            RegistrationService.send($scope.data.entity.id).success(function(response){
                $('.js-response-error').remove();
                if(response.error){
                    var focused = false;
                    Object.keys(response.data).forEach(function(field, index){
                        var $el;
                        if(field === 'projectName'){
                            $el = $('#projectName').parent().find('.label');
                        }else if(field === 'category'){
                            $el = $('.js-editable-registrationCategory').parent();
                        }else if(field.indexOf('agent') !== -1){
                            $el = $('#' + field).parent().find('.registration-label');
                        }else {
                            $el = $('#' + field).find('div:first');
                        }
                        var message = response.data[field] instanceof Array ? response.data[field].join(' ') : response.data[field];
                        message = message.replace(/"/g, '&quot;');
                        $scope.data.propLabels.forEach(function(prop){
                            message = message.replace('{{'+prop.name+'}}', prop.label);
                        });
                        $el.append('<span title="' + message + '" class="danger hltip js-response-error" data-hltip-classes="hltip-danger"></span>');
                        if(!focused){
                            $('html,body').animate({scrollTop: $el.parents('li').get(0).offsetTop - 10}, 300);
                            focused = true;
                        }
                    });
                    MapasCulturais.Messages.error(labels['correctErrors']);
                }else{
                    MapasCulturais.Messages.success(labels['registrationSent']);
                    document.location = response.singleUrl;
                }
            });
        };

    }]);
})(angular);