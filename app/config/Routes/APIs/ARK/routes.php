<?php

$APIs->add('/ark/query/status/{ip}', [
    'namespace'     => 'GameAPIs\Controllers\APIs\ARK\Query\Status',
    'controller'    => 'index',
    'action'        => 'index'
]);

$APIs->add('/ark/query/info/{ip}', [
    'namespace'     => 'GameAPIs\Controllers\APIs\ARK\Query\Info',
    'controller'    => 'index',
    'action'        => 'index'
]);

$APIs->add('/ark/query/players/{ip}', [
    'namespace'     => 'GameAPIs\Controllers\APIs\ARK\Query\Players',
    'controller'    => 'index',
    'action'        => 'index'
]);

 ?>
