<?php
$can_edit = $entity->canUser('modifyRegistrationFields');

$editable_class = $can_edit ? 'js-editable' : '';

?>
<div id="registration-categories" class="registration-fieldset" ng-controller="CategoriesController">
    <h4>Tipologia de atuação</h4>
    <p class="registration-help" >É possível selecionar multiplas áreas e segmentos.</p>
    <div ng-repeat="(k,s) in tipologias">
        <p>
            <h5><?php \MapasCulturais\i::_e("Área");?></h4>
            {{k}}
        </p>

        <p>
            <ul>
                <li ng-repeat="segm in s">{{segm}}</li>
            </ul>
        </p>

    </div>
</div>