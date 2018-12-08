<?php

namespace GameAPIs\Controllers\APIs\Minecraft\Query\MOTD;

use GameAPIs\Libraries\Minecraft\Query\MCPing;
use Redis;
use Phalcon\Filter;

class IndexController extends ControllerBase {

    public function indexAction() {
        $params = $this->dispatcher->getParams();
        if(empty($params['ip'])) {
            $output['error'] = "Please provide an address";
            $output['code'] = 001;
            echo json_encode($output, JSON_PRETTY_PRINT);
        } else {
            if(strpos($params['ip'],',')) {
                $this->dispatcher->forward(
                    [
                        "namespace"     => "GameAPIs\Controllers\APIs\Minecraft\Query\MOTD",
                        "controller"    => "index",
                        "action"        => "multi"
                    ]
                );
            } else {
                $this->dispatcher->forward(
                    [
                        "namespace"     => "GameAPIs\Controllers\APIs\Minecraft\Query\MOTD",
                        "controller"    => "index",
                        "action"        => "single"
                    ]
                );
            }
        }
    }

    public function singleAction() {
        $params = $this->dispatcher->getParams();
        $filter = new Filter();
        if(strpos($params['ip'], ':')) {
            $explodeParams = explode(':', $params['ip']);
            $params['ip'] = $explodeParams[0];
            $params['port'] = (int) $explodeParams[1];
        } else {
            $params['port'] = 25565;
        }
        $cConfig = array();
        $cConfig['ip']   = $filter->sanitize($params['ip'], 'string');
        $cConfig['port'] = $params['port'] ?? 25565;

        $cConfig['redis']['host'] = $this->config->application->redis->host;
        $cConfig['redis']['key']  = $this->config->application->redis->keyStructure->mcpc->ping.$cConfig['ip'].':'.$cConfig['port'];

        $redis = new Redis();
        $redis->pconnect($cConfig['redis']['host']);
        if($redis->exists($cConfig['redis']['key'])) {
            $response = json_decode(base64_decode($redis->get($cConfig['redis']['key'])),true);
            if(!$response['online']) {
                $output['status']          = $response['online'];
                $output['hostname']        = $response['hostname'];
                $output['port']            = $cConfig['port'];
                $output['protocol']        = "tcp";
                $output['error']           = $response['error'];
            } else {
            	$output['status']          = $response['online'];
            	$output['hostname']        = $response['hostname'];
            	$output['port']            = $response['port'];
                $output['protocol']        = "tcp";
            	$output['ping']            = $response['ping'];
                $output['motds']['ingame'] = $response['motd'];
                $output['motds']['html']   = $response['htmlmotd'];
                $output['motds']['clean']  = $response['cleanmotd'];
            }
            $output['cached'] = true;
        } else {
            $status    = new MCPing();
            $getStatus = $status->GetStatus($params['ip'], $params['port']);
            $response  = $getStatus->Response();
            if($response['error'] == "Server returned too little data.") {
                $status    = new MCPing();
                $getStatus = $status->GetStatus($params['ip'], $params['port'], true);
                $response  = $getStatus->Response();
            }
            $response['htmlmotd']  = $getStatus->MotdToHtml($response['motd']);
            $response['cleanmotd'] = $getStatus->ClearMotd($response['motd']);

            if(!$response['online']) {
                $output['status']          = $response['online'];
                $output['hostname']        = $response['hostname'];
                $output['port']            = $cConfig['port'];
                $output['protocol']        = "tcp";
                $output['error']           = $response['error'];
            } else {
                $output['status']          = $response['online'];
                $output['hostname']        = $response['hostname'];
                $output['port']            = $response['port'];
                $output['protocol']        = "tcp";
                $output['ping']            = $response['ping'];
                $output['motds']['ingame'] = $response['motd'];
                $output['motds']['html']   = $response['htmlmotd'];
                $output['motds']['clean']  = $response['cleanmotd'];
            }
            $output['cached'] = false;
            $redis->set($this->config->application->redis->keyStructure->mcpc->ping.$params['ip'].':'.$params['port'], base64_encode(json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), 15);
        }
        header("Cache-Control: s-maxage=15, max-age=15");
        echo json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    public function multiAction() {
        $params = $this->dispatcher->getParams();
        $explodeComma = explode(',', $params['ip']);
        unset($params['ip']);
        $i=0;
        $cConfig = array();

        $cConfig['redis']['host'] = $this->config->application->redis->host;
        $redis = new Redis();
        $redis->pconnect($cConfig['redis']['host']);
        foreach ($explodeComma as $key => $value) {
            if(strpos($value, ':')) {
                $explodeParams = explode(':', $value);
                $cConfig['addresses'][$i]['ip'] = $explodeParams[0];
                $cConfig['addresses'][$i]['port'] = (int) $explodeParams[1];
            } else {
                $cConfig['addresses'][$i]['ip'] = $value;
                $cConfig['addresses'][$i]['port'] = 25565;
            }
            $i++;
        }
        foreach ($cConfig['addresses'] as $key => $value) {
            $combined = $value['ip'].':'.$value['port'];
            $combinedRedis = $this->config->application->redis->keyStructure->mcpc->ping.$combined;
            if($redis->exists($combinedRedis)) {
                $response = json_decode(base64_decode($redis->get($combinedRedis)),true);
                if(!$response['online']) {
                    $output[$combined]['status']            = $response['online'];
                    $output[$combined]['hostname']          = $response['hostname'];
                    $output[$combined]['port']              = $value['port'];
                    $output[$combined]['protocol']          = "tcp";
                    $output[$combined]['error']             = $response['error'];
                } else {
                	$output[$combined]['status']            = $response['online'];
                	$output[$combined]['hostname']          = $response['hostname'];
                	$output[$combined]['port']              = $response['port'];
                    $output[$combined]['protocol']          = "tcp";
                	$output[$combined]['ping']              = $response['ping'];
                    $output[$combined]['motds']['ingame']   = $response['motd'];
                    $output[$combined]['motds']['html']     = $response['htmlmotd'];
                    $output[$combined]['motds']['clean']    = $response['cleanmotd'];
                }
                $output[$combined]['cached'] = true;
            } else {
                $status    = new MCPing();
                $getStatus = $status->GetStatus($value['ip'], $value['port']);
                $response  = $getStatus->Response();
                if($response['error'] == "Server returned too little data.") {
                    $status    = new MCPing();
                    $getStatus = $status->GetStatus($value['ip'], $value['port'], true);
                    $response  = $getStatus->Response();
                }
                $response['htmlmotd']  = $getStatus->MotdToHtml($response['motd']);
                $response['cleanmotd'] = $getStatus->ClearMotd($response['motd']);

                if(!$response['online']) {
                    $output[$combined]['status']            = $response['online'];
                    $output[$combined]['hostname']          = $response['hostname'];
                    $output[$combined]['port']              = $value['port'];
                    $output[$combined]['protocol']          = "tcp";
                    $output[$combined]['error']             = $response['error'];
                } else {
                    $output[$combined]['status']            = $response['online'];
                    $output[$combined]['hostname']          = $response['hostname'];
                    $output[$combined]['port']              = $response['port'];
                    $output[$combined]['protocol']          = "tcp";
                    $output[$combined]['ping']              = $response['ping'];
                    $output[$combined]['motds']['ingame']   = $response['motd'];
                    $output[$combined]['motds']['html']     = $response['htmlmotd'];
                    $output[$combined]['motds']['clean']    = $response['cleanmotd'];
                }
                $output[$combined]['cached'] = false;
                $redis->set($combinedRedis, base64_encode(json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), 15);
            }
        }
        header("Cache-Control: s-maxage=15, max-age=15");
        echo json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

}
