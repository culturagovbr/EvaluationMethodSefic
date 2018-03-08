<?php
use MapasCulturais\i;

$this->addOpportunityEvaluationCommitteeToJs($entity);

$method = $entity->getEvaluationMethod();
?>
<style>
    .committee {
        margin: 1em;
        padding:1em;
        background: #eee;
        border-bottom: 1px solid #aaa;
    }

    .committee .committee--info img {
        height: 48px;
        width: 48px;
        margin-right: 1em;
        float:left;
    }

    .committee .committee--info .committee--name {
        font-size:15px;
        font-weight: bold;
    }

    .committee .committee--fetch {
        margin-top:1em;
    }

    .committee .committee--fetch input:first-of-type {
        width:75px;
    }
    .committee .committee--fetch input:last-of-type {
        width: 80%;
    }

    .committee .committee--fetch input::placeholder {
        color:#bbb;
        font-style: italic;
    }

    .mr10 {
        margin-right: 10px;
    }

    .category-editable {
        display: inline-block;
        padding: 0 3px;
        border-radius: 2px;
        vertical-align: top;
    }

    .category-editable::after {
        margin-left: 5px;
        content: "l";
        font-size: 1rem;
        line-height: 1rem;
        font-family: "ElegantIcons";
        font-weight: normal;
        font-style: normal;
        vertical-align: initial;
        text-transform: none;
        color: #666;
    }

</style>
<div class="agentes-relacionados" ng-controller="SeficEvaluationCommitteeController">
    <div class="registration-fieldset">
        <h4><?php i::_e('Comissão de Avaliação'); ?></h4>
<!--        --><?php //if($method->fetchRegistrations()): ?>
<!--            <div id='status-info' class="alert info">-->
<!--                <p>-->
<!--                    --><?php //\MapasCulturais\i::_e("Explicação da divisão por segmento")?>
<!--                </p>-->
<!--                <div class="close"></div>-->
<!--            </div>-->
<!--        --><?php //endif; ?>
            <div class="committee" ng-repeat="admin in data.committee">
                <div ng-if="admin.status === -5" class="alert warning"><?php i::_e('Aguardando confirmação do avaliador')?></div>
                <div class="committee--info ">
                    <span class="btn btn-danger delete alignright" ng-click="deleteAdminRelation(admin)"><?php i::_e("Excluir");?></span>
                    <span ng-if="admin.hasControl" class="btn btn-warning delete alignright mr10" ng-click="disableAdminRelation(admin)"><?php i::_e("Desabilitar");?></span>
                    <span ng-if="!admin.hasControl" class="btn btn-default add alignright mr10" ng-click="enableAdminRelation(admin)"><?php i::_e("Habilitar");?></span>
                    <img class="committee--avatar" ng-src="{{avatarUrl(admin.agent)}}" />
                    <span class="committee--name" >{{admin.agent.name}}</span>
                    <div ng-if="admin.agent.terms.area">{{admin.agent.terms.area.join(', ')}}</div>
                </div>
                <?php if($method->fetchRegistrations()): ?>
                    <div class="committee--fetch clear">
                        <label class="hltip" title="<?php i::esc_attr_e('Distribuição das inscrições: use para dividir as inscrições entre os avaliadores'); ?>"> <?php i::_e('Distribuição'); ?> </label><br>
<!--                        <input ng-model="config['fetch'][admin.agentUserId]" ng-model-options="{ debounce: 1000, updateOn: 'blur'}" placeholder="--><?php //i::_e('0-9') ?><!--"/>-->
                        <input type="hidden" id="categoria{{$index}}" ng-model="config['fetchCategories'][admin.agentUserId]"  placeholder="<?php i::_e('Categorias separadas por ponto e vírgula') ?>"/>

                            <div data-ng-init="init($index)" ng-controller="SegmentosController">

                                <div ng-repeat="tipologia in tipologiaAtuacao">
                                    <a class="category-editable" id="category" ng-click="editBox.open('eb-tipologia-'+$parent.$index+'-'+$index, $event);"> {{tipologia.nomeSegmento ? tipologia.nomeSegmento : 'Escolha um segmento'}}</a>

                                    <edit-box id="eb-tipologia-{{$parent.$index}}-{{$index}}" index="{{$parent.$index}}" position="bottom" cancel-label="Cancelar" submit-label="Enviar" on-submit="setTypes" on-cancel="setTypes" close-on-cancel="1">
                                        <label>
                                            área:
                                            <select ng-model="tipologia.area" ng-change="set($index)">
                                                <option ng-repeat="(key, val) in tipologia._areas" ng-value="key">{{key}}</option>
                                            </select>
                                        </label>
                                        <label ng-show="tipologia.area">
                                            segmento:
                                            <select ng-model="tipologia.segmento">
                                                <option ng-repeat="(key, val) in tipologia._segmentos" ng-value="key">{{val}}</option>
                                            </select>
                                        </label>
                                    </edit-box>

                                </div>
                                <a class="btn btn-default add" ng-click="adicionarSegmento()" >Adicionar Segmento</a>

                                <a ng-if="tipologiaAtuacao.length > 1" class="btn btn-danger delete" ng-show="tipologiaAtuacao" ng-click="removerSegmento($index)">Remover</a>
                            </div>
                    </div>
                <?php endif; ?>
            </div>
        <p ng-if="committee.length < 1"><?php i::_e('Não há nenhum avaliador definido.'); ?></p>
        <span class="btn btn-default add" ng-click="editbox.open('add-committee-agent', $event)" ><?php i::esc_attr_e('Adicionar avaliador'); ?></span>

        <edit-box ng-if="isEditable" id="add-committee-agent" position="right" title="Adicionar agente à comissão de avaliadores" cancel-label="Cancelar" close-on-cancel='true'>
            <find-entity entity="agent" api-query="findQuery" no-results-text="<?php i::esc_attr_e('Nenhum agente encontrado'); ?>" description="" spinner-condition="false" select="createAdminRelation"></find-entity>
        </edit-box>
    </div>
</div>
