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

    'Remove duplicated evaluations' => function () use ($conn, $app) {
        $inscricoes = $conn->fetchAll("
            SELECT re.registration_id
			FROM registration_evaluation re
			JOIN registration r ON r.id = re.registration_id AND r.opportunity_id = 1275
			GROUP BY registration_id
			HAVING count(re.id) > 1
			ORDER BY registration_id;
        ");

        $inscricoes = array_column($inscricoes, 'registration_id');

        foreach ($inscricoes as $i) {
            $avaliacoes = $conn->fetchAll("
                SELECT id,user_id
                FROM registration_evaluation re
                WHERE re.registration_id = $i and re.user_id != 25089
                ORDER BY re.id;
            ");

            $apagar_avaliacao[] = "
                DELETE
                FROM registration_evaluation re
                WHERE re.id = {$avaliacoes[0]['id']};
            ";

            $apagar_pcache[] = "
                DELETE FROM pcache
                WHERE
                    object_id = $i
                    AND object_type = 'MapasCulturais\Entities\Registration'
                    AND user_id = {$avaliacoes[0]['user_id']}
                    AND action IN ('evaluate', 'view', 'viewPrivateData', 'viewPrivateFiles', 'viewUserEvaluation');
            ";
        }

        try {
            $conn->beginTransaction();

            foreach ($apagar_avaliacao as $q) {
                $conn->executeQuery($q);
            }

            foreach ($apagar_pcache as $q) {
                $conn->executeQuery($q);
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $app->log->debug($e->getMessage());
        }
    },

    'Remove categories from inactive evaluators' => function() use($conn, $app) {
        // Remover as categorias dos avaliadores André (agent 57470 e usr 29120), Ronaldo (agent 51035 e usr 25089) e Adriana (agent 49295 e usr 23755)
        // Na tabela evaluationmethodconfiguration_meta, chave 'fetchCategories'
        $categories = $conn->fetchAll("
            SELECT *
            FROM evaluationmethodconfiguration_meta
            WHERE object_id=1008 and key='fetchCategories'
        ");

        $evaluator_categories = json_decode($categories[0]['value'], true);

        $evaluator_categories[29120] = '';
        $evaluator_categories[25089] = '';
        $evaluator_categories[23755] = '';

        $evaluator_categories = json_encode($evaluator_categories);

        $update_categories = "
            UPDATE evaluationmethodconfiguration_meta
            SET value = '$evaluator_categories'
            WHERE id = {$categories[0]['id']}
        ";

        // Pegar avaliações pendentes com eles em que eles não estão na lista de exceção
        $incorretas_ronaldo = $conn->fetchAll("
            SELECT
                distinct r.id
            FROM
                registration r
            LEFT JOIN
                registration_evaluation re ON re.registration_id = r.id and
                re.user_id = 25089
            JOIN
                pcache p ON r.id = p.object_id and
                p.object_type = 'MapasCulturais\Entities\Registration' and
                p.user_id = 25089 and
                p.action = 'evaluate'
            WHERE
                r.opportunity_id = 1275 and
                r.valuers_exceptions_list != '{\"include\": [25089], \"exclude\": []}' and
                re.id is null;
        ");

        $incorretas_andre = $conn->fetchAll("
            SELECT
                distinct r.id
            FROM
                registration r
            LEFT JOIN
                registration_evaluation re ON re.registration_id = r.id and
                re.user_id = 29120
            JOIN
                pcache p ON r.id = p.object_id and
                p.object_type = 'MapasCulturais\Entities\Registration' and
                p.user_id = 29120 and
                p.action = 'evaluate'
            WHERE
                r.opportunity_id = 1275 and
                r.valuers_exceptions_list != '{\"include\": [], \"exclude\": []}' and
                re.id is null;
        ");

        // $inscricoes = array_merge($pendencias_incorretas_a, $pendencias_incorretas_r);

        foreach($incorretas_andre as $i) {
            $insc_andre[] = $i['id'];
        }

        $insc_id = implode(',', $insc_andre);

        // Remover as entradas no pcache desses avaliadores com essas inscrições
        $delete_pcache_andre = "
            DELETE FROM pcache
            WHERE
                object_id IN ($insc_id)
                AND object_type = 'MapasCulturais\Entities\Registration'
                AND user_id = 29120
                AND action IN ('evaluate', 'view', 'viewPrivateData', 'viewPrivateFiles', 'viewUserEvaluation');
        ";

        foreach($incorretas_ronaldo as $i) {
            $insc_ronaldo[] = $i['id'];
        }

        $insc_id = implode(',', $insc_ronaldo);

        $delete_pcache_ronaldo = "
            DELETE FROM pcache
            WHERE
                object_id IN ($insc_id)
                AND object_type = 'MapasCulturais\Entities\Registration'
                AND user_id = 25089
                AND action IN ('evaluate', 'view', 'viewPrivateData', 'viewPrivateFiles', 'viewUserEvaluation');
        ";

        $pendencias_adriana = $conn->fetchAll( "
            SELECT
                distinct r.id, r.number
            FROM
                registration r
            LEFT JOIN
                registration_evaluation re ON re.registration_id = r.id and
                re.user_id = 23755
            JOIN
                pcache p ON r.id = p.object_id and
                p.object_type = 'MapasCulturais\Entities\Registration' and
                p.user_id = 23755 and
                p.action = 'evaluate'
            WHERE
                r.opportunity_id = 1275 and
                r.valuers_exceptions_list = '{\"include\": [23755], \"exclude\": []}' and
                re.id is null;
        ");

        $pendencias_ronaldo = $conn->fetchAll("
            SELECT
                distinct r.id, r.number
            FROM
                registration r
            LEFT JOIN
                registration_evaluation re ON re.registration_id = r.id and
                re.user_id = 25089
            JOIN
                pcache p ON r.id = p.object_id and
                p.object_type = 'MapasCulturais\Entities\Registration' and
                p.user_id = 25089 and
                p.action = 'evaluate'
            WHERE
                r.opportunity_id = 1275 and
                r.valuers_exceptions_list = '{\"include\": [25089], \"exclude\": []}' and
                re.id is null;
        ");

        $pendencias_andre = $conn->fetchAll("
            SELECT
                distinct r.id, r.number
            FROM
                registration r
            LEFT JOIN
                registration_evaluation re ON re.registration_id = r.id and
                re.user_id = 29120
            JOIN
                pcache p ON r.id = p.object_id and
                p.object_type = 'MapasCulturais\Entities\Registration' and
                p.user_id = 29120 and
                p.action = 'evaluate'
            WHERE
                r.opportunity_id = 1275 and
                r.valuers_exceptions_list = '{\"include\": [], \"exclude\": []}' and
                re.id is null;
        ");

        foreach($pendencias_adriana as $i) {
            $pendentes[] = $i['id'];
            $pendentes_primeira_fase[] = explode('on-', $i['number'])[1];
        }

        foreach($pendencias_ronaldo as $i) {
            $pendentes[] = $i['id'];
            $pendentes_primeira_fase[] = explode('on-', $i['number'])[1];
        }

        foreach($pendencias_andre as $i) {
            $pendentes[] = $i['id'];
            $pendentes_primeira_fase[] = explode('on-', $i['number'])[1];
        }

        $insc_id = implode(',', array_merge($pendentes, $pendentes_primeira_fase));

        $delete_pcache_pendentes = "
            DELETE FROM pcache
            WHERE
                object_id IN ($insc_id)
                AND object_type = 'MapasCulturais\Entities\Registration'
                AND user_id IN (23755, 25089, 29120)
                AND action IN ('evaluate', 'view', 'viewPrivateData', 'viewPrivateFiles', 'viewUserEvaluation');
        ";

        // Incluir avaliadores André, Ronaldo e Adriana como exceção nas inscrições em que eles já avaliaram
        $avaliacoes_andre = $conn->fetchAll("
            SELECT
                r.id, r.number
            FROM
                registration r
            JOIN
                registration_evaluation re ON r.id = re.registration_id
            WHERE
                r.opportunity_id = 1275 and
                re.status in (0,1) and
                re.user_id = 29120;
        ");

        $avaliacoes_ronaldo = $conn->fetchAll("
            SELECT
                r.id, r.number
            FROM
                registration r
            JOIN
                registration_evaluation re ON r.id = re.registration_id
            WHERE
                r.opportunity_id = 1275 and
                re.status in (0,1) and
                re.user_id = 25089;
        ");

        $avaliacoes_adriana = $conn->fetchAll("
            SELECT
                r.id, r.number
            FROM
                registration r
            JOIN
                registration_evaluation re ON r.id = re.registration_id
            WHERE
                r.opportunity_id = 1275 and
                re.status in (0,1) and
                re.user_id = 23755;
        ");

        foreach($avaliacoes_adriana as $a) {
            $reg_id = $a['id'];
            $first_phase_reg_id = explode('on-', $a['number'])[1];

            $update_registrations[] = "
                UPDATE registration
                SET
                    valuers_exceptions_list = '{\"include\": [23755], \"exclude\": []}'
                WHERE
                    id = $reg_id;
            ";
        }

        foreach($avaliacoes_ronaldo as $a) {
            $reg_id = $a['id'];
            $first_phase_reg_id = explode('on-', $a['number'])[1];

            $update_registrations[] = "
                UPDATE registration
                SET
                    valuers_exceptions_list = '{\"include\": [25089], \"exclude\": []}'
                WHERE
                    id = $reg_id;
            ";
        }

        foreach($avaliacoes_andre as $a) {
            $reg_id = $a['id'];
            $first_phase_reg_id = explode('on-', $a['number'])[1];

            $update_registrations[] = "
                UPDATE registration
                SET
                    valuers_exceptions_list = '{\"include\": [29120], \"exclude\": []}'
                WHERE
                    id = $reg_id;
            ";
        }

        // Incluir Rafaela (agent 50986 usr 25061) nessas avaliações pendentes
        // Inserir na pcache e no valuers_exception_list das inscrições
        $pendentes_totais = array_merge($pendentes, $pendentes_primeira_fase);
        foreach($pendentes_totais as $p){
            $insert_pcache[] = "
                INSERT INTO pcache (user_id, action, create_timestamp, object_type, object_id)
                        VALUES
                            (25061, 'evaluate', now(), 'MapasCulturais\Entities\Registration', $p),
                            (25061, 'view', now(), 'MapasCulturais\Entities\Registration', $p),
                            (25061, 'viewPrivateData', now(), 'MapasCulturais\Entities\Registration', $p),
                            (25061, 'viewPrivateFiles', now(), 'MapasCulturais\Entities\Registration', $p),
                            (25061, 'viewUserEvaluation', now(), 'MapasCulturais\Entities\Registration', $p)
            ";
        }

        $insc_id = implode(',', array_merge($pendentes, $pendentes_primeira_fase));

        $update_registrations[] = "
            UPDATE registration
                SET
                    valuers_exceptions_list = '{\"include\": [25061], \"exclude\": []}'
                WHERE
                    id IN ($insc_id);
        ";

        try {
            $conn->beginTransaction();

            $conn->executeQuery($update_categories);

            $conn->executeQuery($delete_pcache_andre);
            $conn->executeQuery($delete_pcache_ronaldo);
            $conn->executeQuery($delete_pcache_pendentes);

            foreach($update_registrations as $q) {
                $conn->executeQuery($q);
            }

            foreach($insert_pcache as $q) {
                $conn->executeQuery($q);
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $app->log->debug($e->getMessage());
        }

    },

    'Fix permission cache for opportunity 1275' => function() use($conn, $app) {
        $avaliadores = [
            '10353',
            '12245',
            '15334',
            '15438',
            '23755',
            '24362',
            '25056',
            '25061',
            '25089',
            '25092',
            '25181',
            '28883',
            '28928',
            '29037',
            '29050',
            '29053',
            '29079',
            '29120',
            '29147',
            '29166',
            '29167',
            '29436',
            '313585'
        ];

        foreach($avaliadores as $a){
            $delete_pcache[] = "
                DELETE FROM pcache
                WHERE id IN (
                    SELECT p.id
                    FROM pcache p
                    JOIN registration_evaluation re ON re.registration_id = p.object_id AND re.user_id != $a
                    JOIN registration r ON r.id = p.object_id AND r.opportunity_id = 1275
                    WHERE p.user_id = $a AND p.object_type = 'MapasCulturais\Entities\Registration'
                );
            ";
        }

        $update_registrations = "
            UPDATE registration
            SET valuers_exceptions_list = '{\"include\": [25061], \"exclude\": []}'
            WHERE id in (
                SELECT r.id
                FROM registration r
                LEFT JOIN registration_evaluation re ON re.registration_id = r.id
                WHERE (re.id is null OR re.user_id = 25061) AND
                    r.opportunity_id = 1275 AND
                    r.status = 1
                ORDER BY r.id
            );
        ";

        $ids_sem_pcache = $conn->fetchAll( "
            SELECT r.id
            FROM registration r
            LEFT JOIN registration_evaluation re ON re.registration_id = r.id
            WHERE re.id is null AND r.opportunity_id = 1275 AND r.status = 1 AND
            r.id NOT IN (
                SELECT r.id from registration r
                LEFT JOIN registration_evaluation re ON re.registration_id = r.id
                JOIN pcache p ON p.object_id = r.id AND p.object_type = 'MapasCulturais\Entities\Registration' AND p.action = 'evaluate'
                WHERE re.id is null AND
                r.opportunity_id = 1275 AND
                r.status = 1 AND
                p.user_id = 25061
            );
        ");

        $ids_sem_pcache = array_column($ids_sem_pcache, 'id');

        foreach($ids_sem_pcache as $i){
            $insert_pcache[] = "
                INSERT INTO pcache (user_id, action, create_timestamp, object_type, object_id)
                    VALUES
                        (25061, 'evaluate', now(), 'MapasCulturais\Entities\Registration', $i),
                        (25061, 'view', now(), 'MapasCulturais\Entities\Registration', $i),
                        (25061, 'viewPrivateData', now(), 'MapasCulturais\Entities\Registration', $i),
                        (25061, 'viewPrivateFiles', now(), 'MapasCulturais\Entities\Registration', $i),
                        (25061, 'viewUserEvaluation', now(), 'MapasCulturais\Entities\Registration', $i)
            ";
        }

        $update_agentrelation = "
            UPDATE agent_relation
            SET status = 1
            WHERE id = (
                SELECT ar.id
                FROM agent_relation ar
                WHERE ar.agent_id = 50986 AND
                    object_id = 1008 AND
                    object_type='MapasCulturais\Entities\EvaluationMethodConfiguration'

            );
        ";

        $categories = $conn->fetchAll("
            SELECT *
            FROM evaluationmethodconfiguration_meta
            WHERE object_id=1008 and key='fetchCategories'
        ");

        $evaluator_categories = json_decode($categories[0]['value'], true);

        $evaluator_categories[25061] = '';

        $evaluator_categories = json_encode($evaluator_categories);

        $update_categories = "
            UPDATE evaluationmethodconfiguration_meta
            SET value = '$evaluator_categories'
            WHERE id = {$categories[0]['id']}
        ";

        try {
            $conn->beginTransaction();

            foreach($delete_pcache as $q){
                $conn->executeQuery($q);
            }

            $conn->executeQuery($update_registrations);

            foreach($insert_pcache as $q){
                $conn->executeQuery($q);
            }

            $conn->executeQuery($update_agentrelation);
            $conn->executeQuery($update_categories);

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $app->log->debug($e->getMessage());
        }

    },

    'Fix valuers exceptions list' => function () use ($conn, $app) {
        $registration = $conn->fetchAll("
            SELECT r.id, r.valuers_exceptions_list, re.user_id
            FROM registration r
            JOIN registration_evaluation re ON re.registration_id = r.id
            WHERE r.opportunity_id = 1275
        ");

        // Conferir se a exception list e a avaliação tem o mesmo ID
        foreach ($registration as $r) {
            $exceptions_list = json_decode($r['valuers_exceptions_list']);
            $evaluator = $r['user_id'];

            if (isset($exceptions_list->include[0]) && $exceptions_list->include[0] !== $evaluator) {
                $update_registrations[] = "
                    UPDATE registration
                    SET valuers_exceptions_list = '{\"include\": [{$evaluator}], \"exclude\": []}'
                    WHERE id = {$r['id']}
                ";
            }
        }

        try {
            $conn->beginTransaction();

            foreach ($update_registrations as $q) {
                $conn->executeQuery($q);
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $app->log->debug($e->getMessage());
        }
    }

);

