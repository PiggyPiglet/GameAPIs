<?php

namespace GameAPIs\Controllers\APIs\Source\Query\Info;

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller {
    public function afterExecuteRoute() {
        $this->view->disable();
        header("Content-Type: application/json; charset=UTF-8");
    }
}
