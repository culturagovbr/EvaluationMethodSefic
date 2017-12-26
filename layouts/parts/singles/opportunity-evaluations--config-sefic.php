<?php 
use MapasCulturais\i;

$configuration = $entity->evaluationMethodConfiguration;
$definition = $configuration->definition;
$evaluationMethod = $definition->evaluationMethod;
$config_form_part_name = $evaluationMethod->getConfigurationFormPartName();

$slug = $entity->evaluationMethodConfiguration->getEvaluationMethod()->getSlug();
?>
<style>
    .evaluations-config--intro label {
        font-size: .8em;
    }
    .evaluations-config--intro textarea {
        min-width: 300px;
        width: 50%;
        height:80px;
    }
</style>
<div id="evaluations-config" class="aba-content" ng-controller="EvaluationMethodConfigurationController">
    
    <p class="js-editable"><?php echo $definition->name ?> - <em><?php echo $definition->description ?></em></p>

    <?php if($slug === 'sefic') {
        $this->part('singles/opportunity-evaluations--committee-sefic', ['entity' => $entity]);
    } else {
        $this->part('singles/opportunity-evaluations--committee', ['entity' => $entity]);
    }?>

    <?php if($config_form_part_name): ?>
    <div>
        <?php $this->part($config_form_part_name, ['entity' => $entity]) ?>
    </div>
    <hr>
    <?php endif; ?>
    
    <div>
        <h4><?php i::_e('Textos informativos para a fichas de inscrição') ?></h4>
        <div class="evaluations-config--intro">
            <label>
                <?php i::_e('Para todas as inscrições') ?> <br>
                <textarea ng-model="config['infos']['general']" ng-model-options="{ debounce: 1000, updateOn: 'blur' }"></textarea>
            </label>
        </div>
    </div>

</div>
