<?php
use MapasCulturais\i;
?>
<div ng-controller="SeficEvaluationMethodFormController" class="sefic-evaluation-form">
    <section ng-repeat="section in ::data.sections">
        <table>
            <tr>
                <th colspan="2">
                    {{section.name}}
                </th>
            </tr>
            <tr ng-repeat="cri in ::data.criteria" ng-if="cri.sid == section.id">
                <td><label for="{{cri.id}}">{{cri.title}}:</label></td>
                    <td>
                        <select id="{{cri.id}}" name="data[{{cri.id}}]" ng-model="evaluation[cri.id]" ng-if="cri.title == 'Experiência'">
                            <option ng-selected="evaluation[cri.id] == 0" val="0">0</option>
                            <option ng-selected="evaluation[cri.id] == 5" val="5">5</option>
                            <option ng-selected="evaluation[cri.id] == 10" val="10">10</option>
                        </select>

                        <select id="{{cri.id}}" name="data[{{cri.id}}]" ng-model="evaluation[cri.id]" ng-if="cri.title == 'Qualificação'">
                            <option ng-selected="evaluation[cri.id] == 0" val="0">0</option>
                            <option ng-selected="evaluation[cri.id] == 5" val="5">5</option>
                            <option ng-selected="evaluation[cri.id] == 10" val="10">10</option>
                        </select>

                        <select id="{{cri.id}}" name="data[{{cri.id}}]" ng-model="evaluation[cri.id]" ng-if="cri.title == 'Bonificação por experiência'">
                            <option ng-selected="evaluation[cri.id] == 0" val="0">0</option>
                            <option ng-selected="evaluation[cri.id] == 5" val="5">5</option>
                        </select>
                    </td>
                <td>
            </tr>
            <tr class="subtotal">
                <td><?php i::_e('Subtotal')?></td>
                <td>{{subtotalSection(section)}}</td>
            </tr>
        </table>
    </section>
    <hr>
    <label>
        <?php i::_e('Parecer Técnico') ?>
        <textarea name="data[obs]" ng-model="evaluation['obs']"></textarea>
    </label>
    <hr>
    <div class='total'>
        <?php i::_e('Pontuação Total'); ?>: <strong>{{total(total)}}</strong><br>
        <?php i::_e('Pontuação Máxima'); ?>: <strong>{{max(total)}}</strong>
    </div>

</div>

