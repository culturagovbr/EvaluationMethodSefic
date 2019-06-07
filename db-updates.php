<?php
namespace MapasCulturais;

$app = App::i();
$em = $app->em;
$conn = $em->getConnection();

return array(

    'Changing column type for categories' => function() use ($conn){
        $conn->executeQuery("ALTER TABLE registration ALTER COLUMN category TYPE text");
    },

    'Fix registration categories that were saved incorrectly' => function() use ($conn, $app){
        $segmentos = require __DIR__ . '/' . 'tipologia-oportunidades.php';
        $segmentos = call_user_func_array("array_merge", $segmentos);
        $segmento_repetido = "Ações de capacitação e treinamento de pessoal";
        $oportunidade_id = 775;

        $oportunidades = $conn->fetchAll("
            SELECT
                id,
                opportunity_id,
                category
            FROM
                registration
            WHERE
                status = 1
                AND opportunity_id = $oportunidade_id
                AND category not like '%$segmento_repetido%';
        ");

        foreach($oportunidades as $oportunidade){
            $categorias = array();

            foreach(explode(';', $oportunidade['category']) as $categoria){
                $categorias[] = array_search($categoria, $segmentos);
            }

            $oportunidade['category'] = implode(';', $categorias);

            $sql[] = "
                UPDATE
                    registration
                SET
                    category = '{$oportunidade['category']}'
                WHERE
                    id = {$oportunidade['id']}
            ";
        }

        $oportunidades = $conn->fetchAll("
            SELECT
                id
            FROM
                registration
            WHERE
                opportunity_id = $oportunidade_id
                AND (category like '%$segmento_repetido%' OR status = 0)
        ");

        foreach($oportunidades as $oportunidade){
            $sql[] = "
                UPDATE
                    registration
                SET
                    status = 0,
                    category = ''
                WHERE
                    id = {$oportunidade['id']}
            ";
        }

        try{
            $conn->beginTransaction();

            foreach($sql as $query){
                $conn->executeQuery($query);
            }

            $conn->commit();
        }catch (Exception $e){
            $conn->rollback();
            $app->log->debug($e->getMessage());
        }
    },

    'Redistribute pending evaluations in opportunity 1275' => function() use($conn, $app){
        $oportunidade_id = 1275;
        $evaluationmethod_id = 1008;

        $avaliadores_cancelados = array(
            '29120',
            '25089'
        );

        $avaliadores_novos = array(
            array(
                'id' => '29436',
                'agent_id' => '57999',
                'qtd' => 159
            ),
            array(
                'id' => '29147',
                'agent_id' => '57508',
                'qtd' => 109
            ),
            array(
                'id' => '23755',
                'agent_id' => '49295',
                'qtd' => 49
            ),
            array(
                'id' => '29037',
                'agent_id' => '57365',
                'qtd' => 88
            ),
            array(
                'id' => '29079',
                'agent_id' => '57414',
                'qtd' => 89
            ),
            array(
                'id' => '24362',
                'agent_id' => '50071',
                'qtd' => 159
            ),
            array(
                'id' => '25181',
                'agent_id' => '51192',
                'qtd' => 159
            ),
            array(
                'id' => '28883',
                'agent_id' => '57174',
                'qtd' => 109
            ),
            array(
                'id' => '25089',
                'agent_id' => '51035',
                'qtd' => 107
            )
        );

        $permissions = array(
            'evaluate',
            'view',
            'viewPrivateData',
            'viewPrivateFiles',
            'viewUserEvaluation'
        );

        $avaliadores = implode(',', $avaliadores_cancelados);
        $avaliacoes_andre = $conn->fetchAll("
            SELECT
                r.*
            FROM
                registration r
            LEFT JOIN
                registration_evaluation re ON r.id = re.registration_id
            JOIN
                pcache p ON
                    r.id = p.object_id AND
                    p.object_type = 'MapasCulturais\Entities\Registration' AND
                    p.user_id = $avaliadores_cancelados[0] AND
                    p.action = 'evaluate'
            WHERE
                r.opportunity_id = $oportunidade_id AND
                re.id IS NULL;
        ");

        $avaliacoes_ronaldo = $conn->fetchAll("
            SELECT
                r.*
            FROM
                registration r
            LEFT JOIN
                registration_evaluation re ON r.id = re.registration_id
            JOIN
                pcache p ON
                    r.id = p.object_id AND
                    p.object_type = 'MapasCulturais\Entities\Registration' AND
                    p.user_id = $avaliadores_cancelados[1] AND
                    p.action = 'evaluate'
            WHERE
                r.opportunity_id = $oportunidade_id AND
                re.id IS NULL;
        ");

        foreach($avaliacoes_ronaldo as $a) {
            $reg_id[] = $a['id'];
            $first_phase_reg_id[] = explode('on-', $a['number'])[1];
        }

        foreach($avaliacoes_andre as $a) {
            $reg_id[] = $a['id'];
            $first_phase_reg_id[] = explode('on-', $a['number'])[1];
        }

        $reg_id = implode(',', $reg_id);

        $delete_pcache = "
             DELETE FROM pcache WHERE user_id IN ($avaliadores) AND object_id IN ($reg_id) AND object_type = 'MapasCulturais\Entities\Registration';
        ";

        $reg_id = explode(',', $reg_id);

        $offset = 0;
        foreach($avaliadores_novos as $a){
            $user_id = $a['id'];
            $agent_id = $a['agent_id'];

            $registrations = array_slice($reg_id, $offset, $a['qtd'], true);
            $first_phase_registrations = array_slice($first_phase_reg_id, $offset, $a['qtd'], true);

            foreach($registrations as $r){
                $insert_pcache[] = "
                    INSERT INTO pcache (user_id, action, create_timestamp, object_type, object_id)
                    VALUES
                        ($user_id, '$permissions[0]', now(), 'MapasCulturais\Entities\Registration', $r),
                        ($user_id, '$permissions[1]', now(), 'MapasCulturais\Entities\Registration', $r),
                        ($user_id, '$permissions[2]', now(), 'MapasCulturais\Entities\Registration', $r),
                        ($user_id, '$permissions[3]', now(), 'MapasCulturais\Entities\Registration', $r),
                        ($user_id, '$permissions[4]', now(), 'MapasCulturais\Entities\Registration', $r)
                ";
            }

            $registrations = implode(',', $registrations);
            $first_phase_registrations = implode(',', $first_phase_registrations);
            $update_registration[] = "
                UPDATE registration
                SET
                    valuers_exceptions_list = '{\"include\": [$user_id], \"exclude\": []}'
                WHERE
                    id IN ($registrations) OR id IN ($first_phase_registrations);
            ";

            $update_agentrelation[] = "
                UPDATE agent_relation
                SET
                    status = 1
                WHERE
                    object_type = 'MapasCulturais\Entities\EvaluationMethodConfiguration' AND
                    object_id = $evaluationmethod_id AND
                    agent_id = $agent_id
            ";

            $offset += $a['qtd'];
        }

        try {
            $conn->beginTransaction();

            $conn->executeQuery($delete_pcache);

            foreach ($update_registration as $q) {
                $conn->executeQuery($q);
            }

            foreach($insert_pcache as $q) {
                $conn->executeQuery($q);
            }

            foreach($update_agentrelation as $q) {
                $conn->executeQuery($q);
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $app->log->debug($e->getMessage());
        }

    },

    'Remove categories from inactive evaluators' => function() use($conn, $app) {
        // Remover as categorias dos avaliadores André (agent 57470 e usr 29120) e Ronaldo (agent 51035 e usr 25089)
        // Na tabela evaluationmethodconfiguration_meta, chave 'fetchCategories'
        $categories = $conn->fetchAll("
            SELECT *
            FROM evaluationmethodconfiguration_meta
            WHERE object_id=1008 and key='fetchCategories'
        ");

        $evaluator_categories = json_decode($categories[0]['value'], true);

        $evaluator_categories[29120] = '';
        $evaluator_categories[25089] = '';

        $evaluator_categories = json_encode($evaluator_categories);

        $update_categories = "
            UPDATE evaluationmethodconfiguration_meta
            SET value = '$evaluator_categories'
            WHERE id = {$categories[0]['id']}
        ";

        // Pegar avaliações pendentes com eles em que eles não estão na lista de exceção
        $inscricoes = $conn->fetchAll( "
            SELECT
                r.id
            FROM
                registration r
            JOIN
                pcache p ON r.id = p.object_id and
                p.object_type = 'MapasCulturais\Entities\Registration' and
                p.user_id IN (25089, 29120) and
                p.action = 'evaluate'
            WHERE
                r.opportunity_id = 1275 and
                r.valuers_exceptions_list != '{\"include\": [], \"exclude\": []}' and
                r.valuers_exceptions_list != '{\"include\": [25089], \"exclude\": []}';
        ");

        foreach($inscricoes as $i) {
            $insc_id[] = $i['id'];
        }

        $insc_id = implode(',', $insc_id);

        // Remover as entradas no pcache desses avaliadores com essas inscrições
        $delete_pcache = "
            DELETE FROM pcache
            WHERE
                object_id IN ($insc_id)
                AND object_type = 'MapasCulturais\Entities\Registration'
                AND user_id IN (25089, 29120)
                AND action IN ('evaluate', 'view', 'viewPrivateData', 'viewPrivateFiles', 'viewUserEvaluation');
        ";

        try {
            $conn->beginTransaction();

            $conn->executeQuery($update_categories);

            $conn->executeQuery($delete_pcache);

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $app->log->debug($e->getMessage());
        }
    }

);

