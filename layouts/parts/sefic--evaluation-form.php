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
                <td><input id="{{cri.id}}" name="data[{{cri.id}}]" type="number" step="0.1" min="{{cri.min}}" max="{{cri.max}}" ng-model="evaluation[cri.id]" class="hltip" title="Configurações: min: {{cri.min}}<br>max: {{cri.max}}<br>peso: {{cri.weight}}"></td>
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