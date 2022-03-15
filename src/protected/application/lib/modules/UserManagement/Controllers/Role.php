<?php
namespace UserManagement\Controllers;

use MapasCulturais\App;
use MapasCulturais\Traits;

class Role extends \MapasCulturais\Controllers\EntityController {
    
    public function __construct()
    {
        parent::__construct();
        $this->entityClassName = "MapasCulturais\\Entities\\Role";
    }

    use Traits\ControllerAPI,
        Traits\ControllerSoftDelete,
        Traits\ControllerDraft;

    protected $entityClassName = "MapasCulturais\\Entities\\Role";


}