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
    // Diego Alexander
    // Ana Taveira (Agente 57508 Usr 29147)
    // Articio Oliveira (Agente 57365 Usr 20937)
    // Everaldo Silva (Agente 8561 Usr 313585)
    // Guilherme Bruno (Agente 57414 Usr 29079)
    // Mayara Melo (Agente 57379 Usr 29050)
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
            '29147',
            '20937',
            '313585',
            '29079',
            '29050',
            '24362',
            '25181',
            '28883',
            '25089'
        );

        $permissions = array(
            'evaluate',
            'view',
            'viewPrivateData',
            'viewPrivateFiles',
            'viewUserEvaluation'
        );

        $avaliadores = implode(',', $avaliadores_cancelados);
        $avaliacoes_pendentes = $conn->fetchAll("
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
                    p.user_id IN ($avaliadores) AND
                    p.action = 'evaluate'
            WHERE
                r.opportunity_id = $oportunidade_id AND
                re.id IS NULL;
        ");

        // Remover o avaliador atual das avaliações pendentes

        // Incluir novos avaliadores na registration->valuers_exception_list

        // Incluir permissões para os avaliadores na pcache

        return false;

    }

);

