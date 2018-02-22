(function (angular) {
    "use strict";

    var module = angular.module('ng.evaluationMethod.sefic', ['ngSanitize']);

    module.config(['$httpProvider', function ($httpProvider) {
        $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
        $httpProvider.defaults.headers.patch['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
        $httpProvider.defaults.transformRequest = function (data) {
            var result = angular.isObject(data) && String(data) !== '[object File]' ? $.param(data) : data;

            return result;
        };
    }]);

    module.factory('SeficEvaluationMethodService', ['$http', '$rootScope', function ($http, $rootScope) {
        return {
            serviceProperty: null,
            getEvaluationMethodConfigurationUrl: function () {
                return MapasCulturais.createUrl('evaluationMethodConfiguration', 'single', [MapasCulturais.evaluationConfiguration.id]);
            },
            patchEvaluationMethodConfiguration: function (entity) {
                entity = JSON.parse(angular.toJson(entity));
                return $http.patch(this.getEvaluationMethodConfigurationUrl(), entity);
            }
        };
    }]);

    module.controller('SeficEvaluationMethodConfigurationController', ['$scope', '$rootScope', '$timeout', 'SeficEvaluationMethodService', 'EditBox', function ($scope, $rootScope, $timeout, SeficEvaluationMethodService, EditBox) {
        $scope.editbox = EditBox;

        var labels = MapasCulturais.gettext.seficEvaluationMethod;

        if(MapasCulturais.evaluationConfiguration && MapasCulturais.evaluationConfiguration.criteria){
            MapasCulturais.evaluationConfiguration.criteria = MapasCulturais.evaluationConfiguration.criteria.map(function(e){
                e.min = parseInt(e.min);
                e.max = parseInt(e.max);
                e.weight = parseInt(e.weight);
                return e;
            });
        }

        $scope.data = {
            sections: MapasCulturais.evaluationConfiguration.sections || [],
            criteria: MapasCulturais.evaluationConfiguration.criteria || [],
            quotas: MapasCulturais.evaluationConfiguration.quotas || [],

            debounce: 2000
        };

        function sectionExists(name) {
            var exists = false;
            $scope.data.sections.forEach(function (s) {
                if (s.name == name) {
                    exists = true;
                }
            });

            return exists;
        }

        $scope.save = function(data){

            data = data || {
                sections: $scope.data.sections,
                criteria: $scope.data.criteria,
                quotas: $scope.data.quotas,
            };
            SeficEvaluationMethodService.patchEvaluationMethodConfiguration(data).success(function () {
                MapasCulturais.Messages.success(labels.changesSaved);
            });
        };

        $scope.addSection = function(){
            var date = new Date;
            var new_id = 's-' + date.getTime();
            $scope.data.sections.push({id: new_id, name: ''});

            $timeout(function(){
                jQuery('#' + new_id + ' header input').focus();
            },1);
        };

        $scope.addSeficSection = function(){
            var seficSection_id = 's-' + new Date().valueOf();
            var criteriaId = 'c-' + new Date().valueOf();

            $scope.data.sections.push({id: seficSection_id, name: '', evaluationMethod: 'sefic'});


            $scope.data.criteria.push({id: criteriaId+1, sid: seficSection_id, title: 'Experiência', min: 0, max: 10, weight:1});
            $scope.data.criteria.push({id: criteriaId+2, sid: seficSection_id, title: 'Qualificação', min: 0, max: 10, weight:1});
            $scope.data.criteria.push({id: criteriaId+3, sid: seficSection_id, title: 'Bonificação por experiência', min: 0, max: 5, weight:1});


            $scope.save();

            $timeout(function(){
                jQuery('#' + seficSection_id + ' header input').focus();
            },1);
        };

        $scope.deleteSection = function(section){
            if(!confirm(labels.deleteSectionConfirmation)){
                return;
            }
            var index = $scope.data.sections.indexOf(section);

            $scope.data.criteria = $scope.data.criteria.filter(function(cri){
                if(cri.sid != section.id){
                    return cri;
                }
            });

            $scope.data.sections.splice(index,1);

            $scope.save();
        }

        $scope.addCriterion = function(section){
            var date = new Date;
            var new_id = 'c-' + date.getTime();
            $scope.data.criteria.push({id: new_id, sid: section.id, title: null, min: 0, max: 10, weight:1});
            $scope.save({criteria: $scope.data.criteria});

            $timeout(function(){
                jQuery('#' + new_id + ' .criterion-title input').focus();S
            },1);
        }

        $scope.deleteCriterion = function(section){
            if(!confirm(labels.deleteCriterionConfirmation)){
                return;
            }
            var index = $scope.data.criteria.indexOf(section);

            $scope.data.criteria.splice(index,1);

            $scope.save({criteria: $scope.data.criteria});
        }
    }]);

    module.controller('SeficEvaluationMethodFormController', ['$scope', '$rootScope', '$timeout', 'SeficEvaluationMethodService', function ($scope, $rootScope, $timeout, SeficEvaluationMethodService) {
        var labels = MapasCulturais.gettext.seficEvaluationMethod;

        MapasCulturais.evaluationConfiguration.criteria = MapasCulturais.evaluationConfiguration.criteria.map(function(e){
            e.min = parseInt(e.min);
            e.max = parseInt(e.max);
            e.weight = parseInt(e.weight);
            return e;
        });

        if(MapasCulturais.evaluation){
            for(var id in MapasCulturais.evaluation.evaluationData){
                if(id != 'obs'){
                    MapasCulturais.evaluation.evaluationData[id] = parseFloat(MapasCulturais.evaluation.evaluationData[id]);
                }
            }
        }

        $scope.data = {
            sections: MapasCulturais.evaluationConfiguration.sections || [],
            criteria: MapasCulturais.evaluationConfiguration.criteria || [],
            empty: true
        };

        if(MapasCulturais.evaluation){
            $scope.evaluation =  MapasCulturais.evaluation.evaluationData;
            $scope.data.empty = false;
        } else {
            $scope.evaluation =  {};
        }

        $scope.subtotalSection = function(section){
            var total = 0;

            for(var i in $scope.data.criteria){
                var cri = $scope.data.criteria[i];
                if(cri.sid == section.id){
                    total += $scope.evaluation[cri.id] * cri.weight;
                }
            }

            return total.toFixed(1);
        };

        $scope.total = function(){
            var total = 0;

            for(var i in $scope.data.criteria){
                var cri = $scope.data.criteria[i];
                total += $scope.evaluation[cri.id] * cri.weight;
            }

            return total.toFixed(1);
        };

        $scope.max = function(){
            var total = 0;

            for(var i in $scope.data.criteria){
                var cri = $scope.data.criteria[i];
                total += cri.max * cri.weight;
            }

            return total;
        };


    }]);

    module.controller('LotEvaluationController',['$scope', '$http', 'RegistrationService', function($scope, $http, RegistrationService){
        $scope.data = {registrationStatusesNames: RegistrationService.registrationStatusesNames};
        $scope.defaultEvaluation = {status: 1};

        $scope.setRegistrationStatus = function(registration,status){

            if (confirm("Deseja realmente avaliar? As alterações não poderão ser desfeitas.") == true) {

                var checkedRegistrations = [];
                var evaluationStatus = $scope.getStatusSlug(status);
                var labels = MapasCulturais.gettext.moduleOpportunity;

                $(".evaluation:checked").each(function() {
                    checkedRegistrations.push($(this).val());
                });

                checkedRegistrations.forEach((registration)=>{
                    $http.post('/inscricoes/setStatusTo/' + registration, {status:evaluationStatus}).then(function (response) {
                    });
                });
                MapasCulturais.Messages.success(labels['changesSaved']);
                location.reload();

            } else {
                return false;
            }
        };

        $scope.getRegistrationStatus = function(registration){
            return registration.status;
        };

        $scope.getStatusSlug = function(status){
            switch (status.value){
                case 0: return 'draft'; break;
                case 1: return 'sent'; break;
                case 2: return 'invalid'; break;
                case 3: return 'notapproved'; break;
                case 8: return 'waitlist'; break;
                case 10: return 'approved'; break;
            }
        };

    }]);
})(angular);