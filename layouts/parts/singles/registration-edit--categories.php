
<!--<?php if($opportunity->registrationCategories): ?>
    <div class="registration-fieldset">
        <h4><?php echo $opportunity->registrationCategTitle ?></h4>
        <p class="registration-help"><?php echo $opportunity->registrationCategDescription ?></p>
        <p>
            <span class='js-editable-registrationCategory' data-original-title="<?php \MapasCulturais\i::esc_attr_e("Opção");?>" data-emptytext="<?php \MapasCulturais\i::esc_attr_e("Selecione uma opção");?>" data-value="<?php echo htmlentities($entity->category) ?>"><?php echo $entity->category ?></span>
        </p>
    </div>
<?php endif; ?>
-->


<div class="registration-fieldset" ng-controller="CategoriesController">
    <h4>Tipologia de categoria</h4>
    <a class="required editable" ng-click="editBox.open('eb-tipologia', $event)"> {{data.segmento ? data.segmento : 'Escolha um segmento'}}</a>

    <edit-box id="eb-tipologia" position="bottom" cancel-label="Cancelar" submit-label="Enviar" on-submit="setTypes" on-cancel="resetValues" close-on-cancel="1">
        <input type="hidden" id="data.area" class="js-editable" data-edit="data.area" data-emptytext="">
        <input type="hidden" id="data.segmento" class="js-editable" data-edit="data.segmento" data-emptytext="">
        <label>
            área:
            <select ng-model="data._tipo1" ng-change="set(1)">
                <option ng-repeat="(key, val) in data._areas" ng-value="key">{{key}}</option>
            </select>
        </label>
        <label ng-show="data._tipo1">
            segmento:
            <select ng-model="data._tipo2">
                <option ng-repeat="val in data._segmentos" ng-value="val">{{val}}</option>
            </select>
        </label>
    </edit-box>
</div>