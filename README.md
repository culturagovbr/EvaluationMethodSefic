# plugin-EvaluationMethodSefic
Plugin do Mapas Culturais para método de avalição de editais da SEFIC

## O que é?
Este plugin é um modo de avaliação alternativo do módulo de oportunidades do [Mapas Culturais](https://github.com/culturagovbr/mapasculturais/tree/feature/opportunities) que supre a atual gestão de edital da SEFIC (Secretaria de Fomento e Incentivo à Cultura).


## Instalação
Para ativar o módulo de oportunidade basta mudar para a branch ```feature/opportunities``` do [Mapas Culturais](https://github.com/culturagovbr/mapasculturais/tree/feature/opportunities) e logo após editar o arquivo de configuração (```src/protected/application/conf/conf-base.php```) com os seguintes parâmetros:

  #### Habilitar o módulo
  ```'app.enabled.opportunities' => true,```
  
  #### Habilitar o plugin
  ```'plugins' => ['EvaluationMethodSefic' => ['namespace' => 'EvaluationMethodSefic'] ]```
