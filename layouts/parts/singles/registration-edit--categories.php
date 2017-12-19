<div class="registration-fieldset" ng-controller="CategoriesController">
    <h4>Tipologia de categoria</h4>
    <div ng-repeat="tipologia in tipologiaAtuacao">
        <a class="required editable" ng-click="editBox.open('eb-tipologia'+$index, $event)"> {{tipologia.segmento ? tipologia.segmento : 'Escolha um segmento'}}</a>

        <edit-box  id="eb-tipologia{{$index}}"position="bottom" cancel-label="Cancelar" submit-label="Enviar" on-submit="setTypes">
            <label>
                área:
                <select ng-model="tipologia.area" ng-change="set($index)">
                    <option ng-repeat="(key, val) in tipologia._areas" ng-value="key">{{key}}</option>
                </select>
            </label>
            <label ng-show="tipologia.area">
                segmento:
                <select ng-model="tipologia.segmento">
                    <option ng-repeat="val in tipologia._segmentos" ng-value="val">{{val}}</option>
                </select>
            </label>

        </edit-box>

    </div>
    <a class="btn btn-danger delete" ng-click="removerSegmento()">Remover</a>
    <a class="btn btn-default add" ng-click="adicionarSegmento()">Novo Segmento</a>
</div>