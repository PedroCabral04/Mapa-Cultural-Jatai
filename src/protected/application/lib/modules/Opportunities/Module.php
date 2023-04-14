<?php

namespace Opportunities;

use DateTime;
use Exception;
use MapasCulturais\App;
use MapasCulturais\i;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\Entities\EvaluationMethodConfiguration;
use MapasCulturais\Entities\Registration;
use PHPUnit\Util\Annotation\Registry;

class Module extends \MapasCulturais\Module{

    function __construct(array $config = [])
    {
        $app = App::i();
        parent::__construct($config);
    }

    function _init(){

        /** @var App $app */
        $app = App::i();

        // Registro de Jobs
        $app->registerJobType(new Jobs\StartEvaluationPhase(Jobs\StartEvaluationPhase::SLUG));
        $app->registerJobType(new Jobs\StartDataCollectionPhase(Jobs\StartDataCollectionPhase::SLUG));
        $app->registerJobType(new Jobs\FinishEvaluationPhase(Jobs\FinishEvaluationPhase::SLUG));
        $app->registerJobType(new Jobs\FinishDataCollectionPhase(Jobs\FinishDataCollectionPhase::SLUG));
        $app->registerJobType(new Jobs\PublishResult(Jobs\PublishResult::SLUG));

        $app->hook('entity(Opportunity).validations', function(&$validations) {
            if (!$this->isNew() && !$this->isLastPhase) {
                $validations['registrationFrom']['required'] = i::__('A data inicial das inscrições é obrigatória');
                $validations['registrationTo']['required'] = i::__('A data final das inscrições é obrigatória');
                $validations['shortDescription']['required'] = i::__('A descrição curtá é obrigatória');
            }
        });

        $app->hook("entity(Opportunity).publish:after", function() use ($app){
            /** @var Opportunity $this */

            foreach($this->allPhases as $phase) {
                $phase->save();

                if($phase->evaluationMethodConfiguration) {
                    $phase->evaluationMethodConfiguration->save();
                }
            }
        });

        $app->hook("entity(Opportunity).save:finish", function() use ($app){
            /** @var Opportunity $this */            
            $data = ['opportunity' => $this];

            // verifica se a oportunidade e a fase estão públicas
            $active = in_array($this->status, [-1, Opportunity::STATUS_ENABLED]) && $this->firstPhase->status === Opportunity::STATUS_ENABLED;

            // Executa Job no momento da publicação automática dos resultados da fase
            if($active && $this->autoPublish){
                $app->enqueueOrReplaceJob(Jobs\PublishResult::SLUG, $data, $this->publishTimestamp->format("Y-m-d H:i:s"));
            } else {
                $app->unqueueJob(Jobs\PublishResult::SLUG, $data);
            }

            // Executa Job no início da fase de coleta de dados
            if ($active && $this->registrationFrom) {
                $app->enqueueOrReplaceJob(Jobs\StartDataCollectionPhase::SLUG, $data, $this->registrationFrom->format("Y-m-d H:i:s"));
            } else {
                $app->unqueueJob(Jobs\StartDataCollectionPhase::SLUG, $data);

            }

            // Executa Job no final da fase de coleta de dados
            if ($active && $this->registrationTo) {
                $app->enqueueOrReplaceJob(Jobs\FinishDataCollectionPhase::SLUG, $data, $this->registrationTo->format("Y-m-d H:i:s"));
            } else {
                $app->unqueueJob(Jobs\FinishDataCollectionPhase::SLUG, $data);
            }
        });

        
        $app->hook("entity(EvaluationMethodConfiguration).save:finish ", function() use ($app){
            /** @var EvaluationMethodConfiguration $this */
            $data = [
                'opportunity' => $this->opportunity,
                'phase' => $this,
            ];

            $active = in_array($this->opportunity->status, [-1, Opportunity::STATUS_ENABLED]) && $this->opportunity->firstPhase->status === Opportunity::STATUS_ENABLED;

            // Executa Job no início de fase de avaliação
            if ($active && $this->evaluationFrom) {
                $app->enqueueOrReplaceJob(Jobs\StartEvaluationPhase::SLUG, $data, $this->evaluationFrom->format("Y-m-d H:i:s"));
            }else {
                $app->unqueueJob(Jobs\StartEvaluationPhase::SLUG, $data);

            }

            // Executa Job no início de fase de avaliação
            if ($active && $this->evaluationTo) {
                $app->enqueueOrReplaceJob(Jobs\FinishEvaluationPhase::SLUG, $data, $this->evaluationTo->format("Y-m-d H:i:s"));
            }else {
                $app->unqueueJob(Jobs\FinishEvaluationPhase::SLUG, $data);

            }
        });
        
          //Cria painel de prestação de contas
        $app->hook('GET(panel.opportunities)', function() use($app) {
            $this->requireAuthentication();
            $this->render('opportunities', []);
        });

        $app->hook('GET(panel.registrations)', function() use($app) {
            $this->requireAuthentication();
            $this->render('registrations', []);
        });

        $app->hook('panel.nav', function(&$nav_items){
            $nav_items['opportunities']['items'] = [
                ['route' => 'panel/opportunities', 'icon' => 'opportunity', 'label' => i::__('Minhas oportunidades')],
                ['route' => 'panel/registrations', 'icon' => 'opportunity', 'label' => i::__('Minhas inscrições')],
                ['route' => 'panel/accountability', 'icon' => 'opportunity', 'label' => i::__('Prestações de contas')],
            ];
        });

        $app->hook('Theme::addOpportunityBreadcramb', function($unused = null, $label) use($app) {
            /** @var \MapasCulturais\Themes\BaseV2\Theme $this */
            /** @var Opportunity $entity */
            $entity = $this->controller->requestedEntity;

            if($entity instanceof EvaluationMethodConfiguration) {
                $first_phase = $entity->opportunity->firstPhase;
            } else {
                $first_phase = $entity->firstPhase;
            }

            $breadcrumb = [
                ['label'=> i::__('Painel'), 'url' => $app->createUrl('panel', 'index')],
                ['label'=> i::__('Minhas oportunidades'), 'url' => $app->createUrl('panel', 'opportunities')],
                ['label'=> $first_phase->name, 'url' => $app->createUrl('opportunity', 'edit', [$first_phase->id])]
            ];
            
            if ($entity->isFirstPhase) {
                $breadcrumb[] = ['label'=> i::__('Período de inscrição')];
            } else {
                $breadcrumb[] = ['label'=> $entity->name];
            }
            $breadcrumb[] = ['label'=> $label];
            
            $this->breadcrumb = $breadcrumb;
        });

        $app->hook('Theme::useOpportunityAPI', function () use ($app) {
            /** @var \MapasCulturais\Themes\BaseV2\Theme $this */
            $this->enqueueScript('components', 'opportunities-api', 'js/OpportunitiesAPI.js', ['components-api']);
        });

        $app->hook('Theme::addOpportunityPhasesToJs', function ($unused = null, $opportunity = null) use ($app) {
            /** @var \MapasCulturais\Themes\BaseV2\Theme $this */   
            $this->useOpportunityAPI();         
            if (!$opportunity) {
                $entity = $this->controller->requestedEntity;

                if ($entity instanceof Opportunity) {
                    $opportunity = $entity;
                } else if ($entity instanceof Registration) {
                    $opportunity = $entity->opportunity;
                } else if ($entity instanceof EvaluationMethodConfiguration) {
                    $opportunity = $entity->opportunity;
                } else {
                    throw new Exception();
                }
            }

            $this->jsObject['opportunityPhases'] = $opportunity->firstPhase->phases;
        });

        $app->hook('Theme::addRegistrationFieldsToJs', function ($unused = null, $opportunity = null) use ($app) {
            if (!$opportunity) {
                $entity = $this->controller->requestedEntity;

                if ($entity instanceof Opportunity) {
                    $opportunity = $entity;
                } else if ($entity instanceof Registration) {
                    $opportunity = $entity->opportunity;
                } else {
                    throw new Exception();
                }
            }
            
            $fields = array_merge((array) $opportunity->registrationFileConfigurations, (array) $opportunity->registrationFieldConfigurations);

            usort($fields, function($a, $b) {                
                return $a->displayOrder <=> $b->displayOrder;
            });

            $this->jsObject['registrationFields'] = $fields;
        });

        $app->hook('mapas.printJsObject:before', function() use($app) {
            /** @var \MapasCulturais\Themes\BaseV2\Theme $this */
            $this->jsObject['config']['evaluationMethods'] = $app->getRegisteredEvaluationMethods();
        });
    }

    function register(){
        $app = App::i();
        $controllers = $app->getRegisteredControllers();
        if (!isset($controllers['opportunities'])) {
            $app->registerController('opportunities', Controller::class);
        }

        // after plugin registration that creates the configuration types
        $app->hook('app.register', function(){
            $this->view->registerMetadata(EvaluationMethodConfiguration::class, 'infos', [
                'label' => i::__("Textos informativos para as fichas de avaliação"),
                'type' => 'json',
            ]);
        });
           
    }
}