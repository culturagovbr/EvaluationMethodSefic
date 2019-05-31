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

    // Opportunity ID = 1275
    // Evaluation Method Configuration ID = 1008

    // 1. Pegar todas as inscrições pendentes de avaliação com
    // André (Agente 57570 Usr 29120)
    // Ronaldo (Agente 51035 Usr 25089)

    // 2. Redistribuir igualmente entre:
    // Diego Alexander (Agente 57999 Usr 29436)
    // Ana Taveira (Agente 57508 Usr 29147)
    // Adriana Nunes (Agente 49295 Usr 23755)
    // Articio Oliveira (Agente 57365 Usr 20937)
    // Everaldo Silva (Agente 8561 Usr 313585)
    // Guilherme Bruno (Agente 57414 Usr 29079)
    // Miguel Coral (Agente 50071 Usr 24362)
    // Pablo Santiago (Agente 51192 Usr 25181)
    // Priscilla Bento (Agente 57174 Usr 28883)
    // Ronaldo Gomes (Agente 51035 Usr 25089)
    // - A redistribuição requer:
    // Remover as inscrições pendentes de avaliação dos avaliadores André e Ronaldo
    // Inclusão do user_id na coluna "valuers_exception_list" da tabela registration
    // Inclusão das permissões (evaluate, view, viewPrivateData, viewPrivateFiles, viewUserEvaluation) para avaliação na tabela pcache
    'Redistribute pending evaluations in opportunity 1275' => function() use($conn, $app){
        $oportunidade_id = 1275;

        $avaliadores_cancelados = array(
            '29120',
            '25089'
        );

        $avaliadores_novos = array(
            array(
                'id' => '29436',
                'qtd' => 150
            ),
            array(
                'id' => '29147',
                'qtd' => 100
            ),
            array(
                'id' => '23755',
                'qtd' => 40
            ),
            array(
                'id' => '20937',
                'qtd' => 79
            ),
            array(
                'id' => '313585',
                'qtd' => 79
            ),
            array(
                'id' => '29079',
                'qtd' => 80
            ),
            array(
                'id' => '24362',
                'qtd' => 150
            ),
            array(
                'id' => '25181',
                'qtd' => 150
            ),
            array(
                'id' => '28883',
                'qtd' => 100
            ),
            array(
                'id' => '25089',
                'qtd' => 100
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

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $app->log->debug($e->getMessage());
        }

        return false;

    }

);

