<?php

$requestedEntity = $this->controller->requestedEntity;

$data['isActive'] = false;

if($requestedEntity instanceof MapasCulturais\Entities\EvaluationMethodConfiguration){
    $entity = $requestedEntity;
}else if($requestedEntity instanceof MapasCulturais\Entities\Opportunity){
    $entity = $requestedEntity->evaluationMethodConfiguration;
}else if($requestedEntity instanceof MapasCulturais\Entities\Registration){
    $entity = $requestedEntity->opportunity->evaluationMethodConfiguration;
}

    $conn = $app->em->getConnection();
    $user = isset($this->controller->data['user']) ? $app->repo("User")->find($this->controller->data['user']) : $app->user;

    $buildQuery = function($colluns = "*", $params = "", $type = "fetchAll") use ($conn, $entity){
        return $conn->$type("SELECT {$colluns} FROM evaluations e WHERE opportunity_id = {$entity->opportunity->id} {$params}");
    };

    $pending = $buildQuery("DISTINCT count(e.registration_id) as qtd", "AND e.evaluation_status IS NULL AND valuer_user_id = {$user->id}", "fetchAssoc");
    $data['pending'] = $pending['qtd'];
    
    $completed = $buildQuery("DISTINCT count(e.registration_id) as qtd", "AND e.evaluation_status = 1 AND valuer_user_id = {$user->id}", "fetchAssoc");
    $data['completed'] = $completed['qtd'];
    
    $send = $buildQuery("DISTINCT count(e.registration_id) as qtd", "AND e.evaluation_status = 2 AND valuer_user_id = {$user->id}", "fetchAssoc");
    $data['send'] = $send['qtd'];
  
    $started = $conn->fetchAssoc("SELECT DISTINCT count(e.registration_id) as qtd FROM registration_evaluation e WHERE e.status = 0 and e.registration_id IN (select r.id from registration r where r.opportunity_id = {$entity->opportunity->id}) AND user_id = {$user->id}");
    $data['started'] = $started['qtd'];
    $data['isActive'] =  true;

$this->jsObject['config']['summaryEvaluate'] = $data;

