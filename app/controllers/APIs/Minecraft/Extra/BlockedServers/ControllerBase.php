<?php

namespace GameAPIs\Controllers\APIs\Minecraft\Extra\BlockedServers;

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller {
    public function beforeExecuteRoute() {
        $this->view->disable();
        header("Content-Type: application/json; charset=UTF-8");
    }
}
