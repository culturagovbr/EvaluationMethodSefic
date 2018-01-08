# plugin EvaluationMethodSefic
Plugin do Mapas Culturais para método de avalição de editais da SEFIC

## O que é?
Este plugin é um modo de avaliação alternativo do módulo de oportunidades do [Mapas Culturais](https://github.com/culturagovbr/mapasculturais/tree/feature/opportunities) que supre a atual gestão de edital da SEFIC (Secretaria de Fomento e Incentivo à Cultura).


## Instalação
  #### Branchs
  Para ativar o módulo de oportunidade é necessário estar na branch ```feature/opportunities``` do [Mapas Culturais](https://github.com/culturagovbr/mapasculturais/tree/feature/opportunities).
  Também é preciso editar o arquivo de configuração (```src/protected/application/conf/conf-base.php```) com os seguintes parâmetros para ativar o módulo:
  
  ```'app.enabled.opportunities' => true,```
  
  #### Clonar o plugin para a pasta plugins do projeto
  ```bash
  cd /mapasculturais/src/protected/application/plugins
  git clone https://github.com/culturagovbr/EvaluationMethodSefic.git
  ```
  
  #### Habilitar o plugin ```EvaluationMethodSefic```
  ```php
  'plugins' => [
      'EvaluationMethodSefic' => ['namespace' => 'EvaluationMethodSefic']
  ]
  ```
  #### Executar as alterações de banco para o plugin

  ```bash
  cd /mapasculturais/scripts
  ./deploy.sh
  ```
  ###### Ps: é necessário estar dentro da pasta scripts, pois os caminhos do ```./deploy.sh``` são relativos
