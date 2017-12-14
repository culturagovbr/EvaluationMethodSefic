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

    function enqueueScriptsAndStyles() {
        $app = App::i();
        $app->view->enqueueStyle('app', 'sefic-evaluation-method', 'css/sefic-evaluation-method.css');
        $app->view->enqueueScript('app', 'sefic-evaluation-form', 'js/ng.evaluationMethod.sefic.js', ['entity.module.opportunity']);

        $app->view->localizeScript('seficEvaluationMethod', [
            'sectionNameAlreadyExists' => i::__('Já existe uma seção com o mesmo nome'),
            'changesSaved' => i::__('Alteraçṍes salvas'),
            'deleteSectionConfirmation' => i::__('Deseja remover a seção? Esta ação não poderá ser desfeita e também removerá todas os critérios desta seção.'),
            'deleteCriterionConfirmation' => i::__('Deseja remover este critério de avaliação? Esta ação não poderá ser desfeita.')
        ]);
        $app->view->jsObject['angularAppDependencies'][] = 'ng.evaluationMethod.sefic';
    }

    public function _init() {
        $app = App::i();

        $app->hook('evaluationsReport(sefic).sections', function(Entities\Opportunity $opportunity, &$sections){
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

}
