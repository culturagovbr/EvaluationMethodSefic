<?php
use MapasCulturais\i;
?>
<div ng-controller="SeficEvaluationMethodConfigurationController" class="sefic-evaluation-configuration registration-fieldset">
    <h4><?php i::_e('Critérios') ?></h4>
    <?php i::_e('Configure abaixo os critérios de avaliação técnica') ?>
    <section id="{{section.id}}" ng-repeat="section in data.sections">
        <header>
            <input ng-model="section.name" placeholder="<?php i::_e('informe o título da avaliação') ?>" class="section-name edit" ng-change="save({sections: data.sections})" ng-model-options='{ debounce: data.debounce }'>
<!--            <button ng-if="section.name.trim().length > 0" ng-click="deleteSeficSection(section)" class="btn btn-danger delete alignright">--><?php //i::_e('Remover seção') ?><!--</button>-->
<!--            <button ng-if="section.name.trim().length == 0" ng-click="deleteSeficSection(section)" class="btn btn-default delete alignright">--><?php //i::_e('Cancelar') ?><!--</button>-->
        </header>

 <!--       <table>
            <tr>
                <th class="criterion-title"><?php i::_e('Título do critério') ?></th>
                <th class="criterion-num"><?php i::_e('Mínimo') ?></th>
                <th class="criterion-num"><?php i::_e('Máximo') ?></th>
                <th class="criterion-num"><?php i::_e('Peso') ?></th>
                <th>
                    <button ng-click="addCriterion(section)" class="btn btn-default add" title="<?php i::_e('Adicionar critério') ?>"></button>
                </th>
            </tr>

            <tr id="{{cri.id}}" ng-repeat="cri in data.criteria" ng-if="cri.sid == section.id">
                <td class="criterion-title"><input ng-model="cri.title" placeholder="<?php i::_e('informe o título do critério') ?>" ng-change="save({criteria: data.criteria})" ng-model-options='{ debounce: data.debounce }'></td>
                <td class="criterion-num"><input ng-model="cri.min" type="number" placeholder="<?php i::_e('informe a nota mínima') ?>" ng-change="save({criteria: data.criteria})" ng-model-options='{ debounce: data.debounce }'></td>
                <td class="criterion-num"><input ng-model="cri.max" type="number" placeholder="<?php i::_e('informe a nota máxima') ?>" ng-change="save({criteria: data.criteria})" ng-model-options='{ debounce: data.debounce }'></td>
                <td class="criterion-num"><input ng-model="cri.weight" type="number" placeholder="<?php i::_e('informe o peso da nota') ?>" ng-change="save({criteria: data.criteria})" ng-model-options='{ debounce: data.debounce }'></td>
                <td>
                    <button ng-click="deleteCriterion(cri)" class="btn btn-danger delete" title="<?php i::_e('Remover critério') ?>"></button>
                </td>
            </tr>
        </table>
-->
        <div>
            <label><strong>Experiência Profissional no(s) segmento(s) pleiteado(s):</strong></label>
            <ul>
                <li>Igual ou superior a 5 anos: 10 pontos.</li>
                <li>De 2 anos a 4 anos: 5 pontos.</li>
                <li>A pontuação não é cumulava.</li>
                <li>Somente será considerada a experiência profissional relativa aos últimos 10 (dez) anos.</li>
            </ul>

            <label><strong>Qualificação e Titulação:</strong></label>
            <ul>
                <li>Pós-graduação e graduação específicas no(s) segmentos(s) pleiteado(s): 10 pontos</li>
                <li>Graduação específica no(s) segmentos(s) pleiteado(s): 5 pontos</li>
                <li>Graduação: 2 pontos</li>
                <li>
                    O candidato que comprovar experiência, igual ou superior a 2 anos, em análise e emissão de parecer de projetos culturais, será bonificado em 5 pontos.
                </li>
            </ul>
            Será eliminado o candidato que não obter,no mínimo, a pontuação 15.
            <br/>

        </div>
    </section>
<!--    <button ng-click="addSection()" class="btn btn-default add">--><?php //i::_e('Adicionar seção de avaliação técnica') ?><!--</button>-->
    <button ng-if="!data.sections[0]" ng-click="addSeficSection()" class="btn btn-default add"><?php i::_e('Adicionar método de avaliação Sefic') ?></button>
</div>