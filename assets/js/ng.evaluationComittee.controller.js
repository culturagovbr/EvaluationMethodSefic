(function (angular) {
    "use strict";

    var module = angular.module('evaluationComittee.controller', ['ngSanitize', 'checklist-model']);

    function getOpportunityId(){
        if(MapasCulturais.request.controller == 'registration'){
            return MapasCulturais.entity.object.opportunity.id;
        } else {
            return MapasCulturais.entity.id;
        }
    }

    module.controller('SegmentosController',['$scope', 'EditBox', function($scope, EditBox){
        $scope.editBox = EditBox;
        $scope.tipologias = MapasCulturais.segmentos;

        $scope.init = function(index){
            var userId = $scope.data.committee[index].agentUserId;

            if($scope.config.fetchCategories === null){
                var categories = [];
            }else{
                var categories = $scope.config.fetchCategories[userId] ? $scope.config.fetchCategories[userId].split(";") : [];
            }

            $scope.tipologiaAtuacao =
                [
                    {
                        _areas: $scope.tipologias,
                        _segmentos: [],
                    }
                ];

            angular.forEach(categories, (category, i) => {
                $scope.tipologiaAtuacao[i] =
                    {
                        _areas: $scope.tipologias,
                        _segmentos: [],
                        segmento: category
                    };
            });
        };

        $scope.adicionarSegmento = function() {
            $scope.tipologiaAtuacao.push(
                {
                    _areas: $scope.tipologias,
                    _segmentos: []
                }
            );

        };

        $scope.removerSegmento = function(index) {
            var inputValues = [];
            var ultimoSegmento = $scope.tipologiaAtuacao.length-1;
            $scope.tipologiaAtuacao.splice(ultimoSegmento);

            angular.forEach($scope.tipologiaAtuacao, function(val){
                inputValues.push(val.segmento);
            });

            $scope.config['fetchCategories'][$scope.admin.agentUserId] = inputValues.join(';');
        };

        $scope.set = function(index){
            $scope.tipologiaAtuacao[index]._segmentos = $scope.tipologias[$scope.tipologiaAtuacao[index].area];
        };

        $scope.setTypes = function(element){
            var index = element.index;

            $scope.changeValues(index);

            var $box = jQuery('[id^=eb-tipologia]').find('>div.edit-box');
            $box.hide();
            jQuery('[id^=eb-tipologia]').trigger('close');
        };


        $scope.changeValues = function(index){
            var inputValues = [];

            angular.forEach($scope.tipologiaAtuacao, function(val){
                inputValues.push(val.segmento);
            });

            $scope.config['fetchCategories'][$scope.admin.agentUserId] = inputValues.join(';');
        };

    }]);

    module.controller('SeficEvaluationCommitteeController', ['$scope', 'RelatedAgentsService', 'EvaluationMethodConfigurationService', 'EditBox', 'OpportunityApiService', function($scope, RelatedAgentsService, EvaluationMethodConfigurationService, EditBox, OpportunityApiService) {
        var labels = MapasCulturais.gettext.moduleOpportunity;
        var emconfig = MapasCulturais.entity.object.evaluationMethodConfiguration;

        var committeeApi = new OpportunityApiService($scope, 'committee', 'evaluationCommittee', {'@opportunity': getOpportunityId()});

        $scope.editbox = EditBox;
        RelatedAgentsService = angular.copy(RelatedAgentsService);

        RelatedAgentsService.controllerId = 'evaluationMethodConfiguration';
        RelatedAgentsService.entityId = MapasCulturais.entity.object.evaluationMethodConfiguration.id;

        $scope.groups = [];

        $scope.showCreateDialog = {};

        $scope.spinners = {};

        $scope.isEditable = MapasCulturais.isEditable;
        $scope.canChangeControl = MapasCulturais.entity.canUserCreateRelatedAgentsWithControl;

        $scope.data = {
            entity: MapasCulturais.entity,
            categories: MapasCulturais.entity.registrationCategories,
            committee: [],

        };

        committeeApi.find().success(function(result){
            $scope.data.committee = result;
        });

        $scope.fetch = emconfig.fetch || {};
        $scope.fetchCategories = emconfig.fetchCategories || {};

        $scope.config = {
            fetch: emconfig.fetch || {},
            fetchCategories: emconfig.fetchCategories || {},
            infos: emconfig.infos
        };

        var lastConfig = angular.copy($scope.config);



        $scope.$watch('config', function(o,n){
            if(angular.equals(lastConfig, $scope.config)){
                return;
            }

            lastConfig = angular.copy($scope.config);

            var promise = EvaluationMethodConfigurationService.patch($scope.config);
            promise.then(function(){
                MapasCulturais.Messages.success(labels['changesSaved']);
            }, function(error){
                console.log('error: ' + error);
            });
        },true);

        $scope.agentRelationDisabledCD = MapasCulturais.agentRelationDisabledCD || [];

        $scope.findQuery = {
            type: 'EQ(1)',
            status: 'GT(0)',
            parent: 'NULL()'
        };

        $scope.$watch('committee',function(o,n){
            var ids = $scope.data.committee.map(function(e){ return e.agent.id; });
            if(ids.length > 0){
                $scope.findQuery.id = '!IN(' + (ids.join(',')) + ')';
            } else {
                delete $scope.findQuery.id;
            }
        },true);

        $scope.disabledCD = function(groupName){
            return $scope.agentRelationDisabledCD.indexOf(groupName) >= 0;
        };


        function getGroup(groupName){
            var result = null;
            $scope.groups.forEach(function(group){
                if(group.name === groupName)
                    result = group;
            });

            return result;
        }

        function groupExists(groupName){
            if(getGroup(groupName))
                return true;
            else
                return false;
        }

        $scope.avatarUrl = function(entity){
            if(entity.avatar.avatarSmall)
                return entity.avatar.avatarSmall.url;
            else
                return MapasCulturais.defaultAvatarURL;
        };

        $scope.closeNewGroupEditBox = function(){
            EditBox.close('new-related-agent-group');
        };

        $scope.closeRenameGroupEditBox = function(){
            EditBox.close('rename-related-agent-group');
        };

        $scope.data.newGroupName = '';

        $scope.getCreateAgentRelationEditBoxId = function(groupName){
            return 'add-related-agent-' + groupName.replace(/[^a-z0-9_]/gi,'');
        };

        $scope.createGroup = function(){
            if($scope.data.newGroupName.trim() && !groupExists( $scope.data.newGroupName ) && $scope.data.newGroupName.toLowerCase().trim() !== 'registration' && $scope.data.newGroupName.toLowerCase().trim() !== 'group-admin' ){
                var newGroup = {name: $scope.data.newGroupName, relations: []};

                $scope.groups = [newGroup].concat($scope.groups);

                $scope.data.newGroupName = '';
                EditBox.close('new-related-agent-group');
            }
        };

        $scope.setRenameGroup = function(group){
            $scope.data.editGroup = {};
            angular.copy(group, $scope.data.editGroup);
            $scope.data.editGroupIndex = $scope.groups.indexOf(group);
        };

        $scope.renameGroup = function(e){
            if($scope.data.editGroup.name.trim() && !groupExists( $scope.data.editGroup.name ) && $scope.data.editGroup.name.toLowerCase().trim() !== 'registration' && $scope.data.editGroup.name.toLowerCase().trim() !== 'group-admin' ){
                RelatedAgentsService.renameGroup($scope.data.editGroup).success(function() {
                    angular.copy($scope.data.editGroup, $scope.groups[$scope.data.editGroupIndex]);
                    EditBox.close('rename-related-agent-group');
                });
            }
        };

        $scope.createRelation = function(entity){
            var _scope = this.$parent;
            var groupName = _scope.attrs.group;

            RelatedAgentsService.create(groupName, entity.id).
            success(function(data){
                var group = getGroup(groupName);
                group.relations.push(data);
                $scope.showCreateDialog[groupName] = false;
                _scope.$parent.searchText = '';
                _scope.$parent.result = [];
                EditBox.close($scope.getCreateAgentRelationEditBoxId(groupName));
            });
        };

        $scope.deleteRelation = function(relation){
            var group = getGroup(relation.group);
            var oldRelations = group.relations.slice();
            var i = group.relations.indexOf(relation);

            group.relations.splice(i,1);

            RelatedAgentsService.remove(relation.group, relation.agent.id).
            error(function(){
                group.relations = oldRelations;
            });
        };

        $scope.deleteGroup = function(group) {
            if (confirm(labels['confirmDeleteGroup'].replace('%s', group.name))) {
                var i = $scope.groups.indexOf(group);
                group.relations.forEach(function(relation){
                    //$scope.deleteRelation(relation);
                    RelatedAgentsService.remove(relation.group, relation.agent.id);
                });

                $scope.groups.splice(i,1);
            }
        };

        $scope.createAdminRelation = function(entity){
            var _scope = this.$parent;
            var groupName = 'group-admin';
            var hasControl = true;

            RelatedAgentsService.create(groupName, entity.id, true).
            success(function(data){
                $scope.data.committee.push(data);
                _scope.$parent.searchText = '';
                _scope.$parent.result = [];
                EditBox.close('add-committee-agent');
            });
        };

        $scope.deleteAdminRelation = function(relation){
            RelatedAgentsService.remove('group-admin', relation.agent.id).
            success(function(){
                var i = $scope.data.committee.findIndex(function(el){
                    return el.id == relation.id;
                });
                $scope.data.committee.splice(i,1);
            });
        };

        $scope.disableAdminRelation = function(relation){
            relation.hasControl = false;
            RelatedAgentsService.removeControl(relation.agent.id).
            error(function(){
                relation.hasControl = true;
            });
        };

        $scope.enableAdminRelation = function(relation){
            relation.hasControl = true;
            RelatedAgentsService.giveControl(relation.agent.id).
            error(function(){
                relation.hasControl = false;
            });
        };


        $scope.toggleControl = function(relation){
            relation.hasControl = !relation.hasControl;

            if(relation.hasControl){
                RelatedAgentsService.giveControl(relation.agent.id).
                error(function(){
                    relation.hasControl = false;
                });
            }else{
                RelatedAgentsService.removeControl(relation.agent.id).
                error(function(){
                    relation.hasControl = true;
                });
            }
        };

        $scope.filterResult = function( data, status ){
            var group = getGroup( this.attrs.group );

            if(group && group.relations.length > 0){
                var ids = group.relations.map( function( el ){ return el.agent.id; } );

                data = data.filter( function( e ){
                    if( ids.indexOf( e.id ) === -1 )
                        return e;
                } );
            }
            return data;
        };
    }]);

})(angular);