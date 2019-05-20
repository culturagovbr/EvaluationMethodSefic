<?php

namespace EvaluationMethodSefic;

use MapasCulturais\i;
use MapasCulturais\App;
use MapasCulturais\Entities;

class Plugin extends \EvaluationMethodTechnical\Plugin {


    public function getSlug() {
        return 'sefic';
    }

    public function getName() {
        return i::__('Avaliação Sefic');
    }

    public function getDescription() {
        return i::__('Consiste em avaliação por critérios, cotas, áreas e segmentos.');
    }

    public function cmpValues($value1, $value2){
        $value1 = (float) $value1;
        $value2 = (float) $value2;

        return parent::cmpValues($value1, $value2);
    }

    protected function _register() {
        $this->registerEvaluationMethodConfigurationMetadata('sections', [
            'label' => i::__('Seções'),
            'type' => 'json',
            'serialize' => function ($val){
                return json_encode($val);
            },
            'unserialize' => function($val){
                return json_decode($val);
            }
        ]);

        $this->registerEvaluationMethodConfigurationMetadata('criteria', [
            'label' => i::__('Critérios'),
            'type' => 'json',
            'serialize' => function ($val){
                return json_encode($val);
            },
            'unserialize' => function($val){
                return json_decode($val);
            }
        ]);

        $this->registerEvaluationMethodConfigurationMetadata('quota', [
            'label' => i::__('Cotas'),
            'type' => 'json',
            'serialize' => function ($val){
                return json_encode($val);
            },
            'unserialize' => function($val){
                return json_decode($val);
            }
        ]);

    }

    function getValidationErrors(Entities\EvaluationMethodConfiguration $evaluation_method_configuration, array $data){
        $errors = [];

        $empty = false;


        foreach($data as $key => $val){
            if($key === 'obs' && !trim($val)){
                $empty = true;
            } else if($key !== 'obs' && !is_numeric($val)){
                $empty = true;
            }
        }

        if($empty){
            $errors[] = i::__('Todos os campos devem ser preenchidos');
        }

        if(!$errors){
            foreach($evaluation_method_configuration->criteria as $c){
                if(isset($data[$c->id])){
                    $val = (float) $data[$c->id];
                    if($val > (float) $c->max){
                        $errors[] = sprintf(i::__('O valor do campo "%s" é maior que o valor máximo permitido'), $c->title);
                        break;
                    } else if($val < (float) $c->min) {
                        $errors[] = sprintf(i::__('O valor do campo "%s" é menor que o valor mínimo permitido'), $c->title);
                        break;
                    }
                }
            }
        }


        return $errors;
    }

    function enqueueScriptsAndStyles() {
        $app = App::i();
        $app->view->enqueueStyle('app', 'sefic-evaluation-method', 'css/sefic-evaluation-method.css');
        $app->view->enqueueScript('app', 'sefic-evaluation-form', 'js/ng.evaluationMethod.sefic.js', ['entity.module.opportunity']);
        $app->view->enqueueScript('app', 'opportunity-controller-categories', 'js/ng.opportunity.controller.categories.js', ['entity.module.opportunity']);
        $app->view->enqueueScript('app', 'registration-controller', 'js/ng.registration.controller.js', ['entity.module.opportunity']);
        $app->view->enqueueScript('app', 'evaluationComittee-controller', 'js/ng.evaluationComittee.controller.js', ['entity.module.opportunity']);
        $app->view->enqueueScript('app', 'registration-number-controller', 'js/ng.registration.number.controller.js', ['entity.module.opportunity']);

        $app->view->localizeScript('seficEvaluationMethod', [
            'sectionNameAlreadyExists' => i::__('Já existe uma seção com o mesmo nome'),
            'changesSaved' => i::__('Alteraçṍes salvas'),
            'deleteSectionConfirmation' => i::__('Deseja remover a seção? Esta ação não poderá ser desfeita e também removerá todas os critérios desta seção.'),
            'deleteCriterionConfirmation' => i::__('Deseja remover este critério de avaliação? Esta ação não poderá ser desfeita.')
        ]);
        $app->view->jsObject['angularAppDependencies'][] = 'ng.evaluationMethod.sefic';
        $app->view->jsObject['angularAppDependencies'][] = 'opportunity.controller.categories';
        $app->view->jsObject['angularAppDependencies'][] = 'registration.controller';
        $app->view->jsObject['angularAppDependencies'][] = 'evaluationComittee.controller';
        $app->view->jsObject['angularAppDependencies'][] = 'registration.number.controller';
        $app->view->jsObject['segmentos'] = require __DIR__ . '/' . 'tipologia-oportunidades.php';
    }

    public function _init() {
        $app = App::i();

        $_hook = "evaluationsReport(".$this->getSlug().").sections";
        $app->hook($_hook, function(Entities\Opportunity $opportunity, &$sections){
            $i = 0;
            $get_next_color = function($last = false) use(&$i){
                $colors = [
                    '#FFAAAA',
                    '#BB8888',
                    '#FFAA66',
                    '#AAFF00',
                    '#AAFFAA'
                ];

                $result = $colors[$i];

                $i++;

                return $result;
            };

            $cfg = $opportunity->evaluationMethodConfiguration;

            $result = [
                'registration' => $sections['registration'],
                'committee' => $sections['committee'],
            ];
            foreach($cfg->sections as $sec){
                $section = (object) [
                    'label' => $sec->name,
                    'color' => $get_next_color(),
                    'columns' => []
                ];

                foreach($cfg->criteria as $crit){
                    if($crit->sid != $sec->id) {
                        continue;
                    }

                    $section->columns[] = (object) [
                        'label' => $crit->title . ' ' . sprintf(i::__('(peso: %s)'), $crit->weight),
                        'getValue' => function(Entities\RegistrationEvaluation $evaluation) use($crit) {
                            return isset($evaluation->evaluationData->{$crit->id}) ? $evaluation->evaluationData->{$crit->id} : '';
                        }
                    ];
                }

                $max = 0;
                foreach($cfg->criteria as $crit){
                    if($crit->sid != $sec->id) {
                        continue;
                    }

                    $max += $crit->max * $crit->weight;
                }

                $section->columns[] = (object) [
                    'label' => sprintf(i::__('Subtotal (max: %s)'),$max),
                    'getValue' => function(Entities\RegistrationEvaluation $evaluation) use($sec, $cfg) {
                        $rersult = 0;
                        foreach($cfg->criteria as $crit){
                            if($crit->sid != $sec->id) {
                                continue;
                            }

                            $val = isset($evaluation->evaluationData->{$crit->id}) ? $evaluation->evaluationData->{$crit->id} : 0;

                            $rersult += $val * $crit->weight;

                        }

                        return $rersult;
                    }
                ];

                $result[] = $section;
            }

            $result['evaluation'] = $sections['evaluation'];
//            $result['evaluation']->color = $get_next_color(true);


            // adiciona coluna do parecer técnico
            $result['evaluation']->columns[] = (object) [
                'label' => i::__('Parecer Técnico'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) use($crit) {
                    return isset($evaluation->evaluationData->obs) ? $evaluation->evaluationData->obs : '';
                }
            ];

            $sections = $result;
        });

        $app->hook('view.partial(singles/opportunity-registrations--tables--manager-technical-sefic):before', function(){

            if($this->controller->action === 'create'){
                return;
            }

            $opportunity = $this->controller->requestedEntity;
            if($opportunity->isOpportunityPhase){
                $this->part('import-last-phase-button', ['entity' => $opportunity]);
            }

        });


        $app->hook('view.partial(singles/registration-single--<<header|categories|agents>>).params', function (&$params, &$template) {
            $opportunity = self::getRequestedOpportunity();

            if (!$opportunity) {
                return;
            }

            if ($opportunity->slug == 'sefic') {
                $params['entity'] = $this->controller->requestedEntity;
                $params['opportunity'] = $opportunity;
                if($template == 'singles/registration-single--categories'){
                    $template = 'singles/registration-single--categories-sefic';
                }
            }
        });


        $app->hook('GET(opportunity.importLastPhaseRegistrations)', function() use($app) {

            $module = new \OpportunityPhases\Module;

            $target_opportunity = $module->getRequestedOpportunity();

            $target_opportunity ->checkPermission('@control');

            if($target_opportunity->previousPhaseRegistrationsImported){
                $this->errorJson(\MapasCulturais\i::__('As inscrições já foram importadas para esta fase'), 400);
            }

            $previous_phase = $module->getPreviousPhase($target_opportunity);

            $registrations = array_filter($previous_phase->getSentRegistrations(), function($item){
                if($item->status === Entities\Registration::STATUS_APPROVED || $item->status === Entities\Registration::STATUS_WAITLIST){
                    return $item;
                }
            });

            if(count($registrations) < 1){
                $this->errorJson(\MapasCulturais\i::__('Não há inscrições aprovadas fase anterior'), 400);
            }

            $new_registrations = [];

            $app->disableAccessControl();
            foreach ($registrations as $r){
                foreach(explode(";", $r->category) as $c){
                    $reg = new Entities\Registration;
                    $reg->owner = $r->owner;
                    $reg->opportunity = $target_opportunity;
                    $reg->status = Entities\Registration::STATUS_DRAFT;
                    $reg->number = $r->number;

                    $reg->previousPhaseRegistrationId = $r->id;
                    $reg->category = $c;
                    $reg->save(false);

                    if(isset($this->data['sent'])){
                        $reg->send(false);
                    }

                    $r->nextPhaseRegistrationId = $reg->id;
                    $r->save(false);

                    $new_registrations[] = $reg;
                }
            }

            $app->em->flush();

            $target_opportunity->previousPhaseRegistrationsImported = true;

            $target_opportunity->save(true);

            $app->enableAccessControl();

            $this->finish($new_registrations);
        }, 0);
    }

    public function _getConsolidatedResult(\MapasCulturais\Entities\Registration $registration) {
        $app = App::i();

        $evaluations = $app->repo('RegistrationEvaluation')->findBy(['registration' => $registration]);

        $result = 0;
        foreach ($evaluations as $eval){
            $result += $this->getEvaluationResult($eval);
        }

        $num = count($evaluations);
        if($num){
            return $result / $num;
        } else {
            return null;
        }
    }

    public function getEvaluationResult(Entities\RegistrationEvaluation $evaluation) {
        $total = 0;

        $cfg = $evaluation->getEvaluationMethodConfiguration();
        foreach($cfg->criteria as $cri){
            $key = $cri->id;
            if(!isset($evaluation->evaluationData->$key)){
                return null;
            } else {
                $val = $evaluation->evaluationData->$key;
                $total += $cri->weight * $val;
            }
        }

        return $total;
    }

    public function valueToString($value) {
        if(is_null($value)){
            return i::__('Avaliação incompleta');
        } else {
            return $value;
        }
    }

    public function fetchRegistrations() {
        return true;
    }

    static function getRequestedOpportunity()
    {
        $app = App::i();

        $opportunity = $app->view->controller->requestedEntity->opportunity;
        $opportunity->slug = $opportunity->evaluationMethodConfiguration->getEvaluationMethod()->getSlug();

        if (!$opportunity) {
            return null;
        }

        return $opportunity;
    }

}
