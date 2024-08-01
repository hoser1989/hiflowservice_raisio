<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->get('/generate_bom', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = $request->getQueryParams();
    $productionOrder = $params['order'];
    $workCenter = substr($params['work_center'],2) ? substr($params['work_center'],2) : '0';
    $topLevel  = $params['top_level'];

    if($topLevel == 'true') {
        $topLevel = '1';
    } else if($topLevel == 'false') {
        $topLevel = '0';
    }

    $db->query("exec show_bom '$productionOrder', '$workCenter', '$topLevel'");
    $result = array();
    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(!empty($result)) {
        //--------------------------------------------------------------encode array to UTF-8 ----------------------------------
        array_walk_recursive($result, function(&$item, $key){
            if(!mb_detect_encoding($item, 'utf-8', true)){
                $item = utf8_encode($item);
            }
        });
        //----------------------------------------------------------------------------------------------------------------------
    } else {
        $result = array(array(
            'PDNO' => 'No data',
            'sortpole' => 'No data',
            'child' => 'No data',
            'ident' => 'No data',
            'sitms' => 'No data',
            'poziom' => 'No data',
            'mitm' => 'No data',
            'pono' => 'No data',
            'seqn' => 'No data',
            'sitm' => 'No data',
            'revi' => 'No data',
            'dsca' => 'No data',
            'qana' => 'No data',
            'quanasum' => 'No data',
            'opno' => 'No data',
            'cwoc' => 'No data',
            'kitm' => 'No data',
            'cpha' => 'No data',
            'exin' => 'No data',
            'type' => 'No data'
        ));
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});


$app->post('/kanban_locations', function (Request $request, Response $response, $args) {

    $db = new database('plsts1-s0044\Shortages', $user = 'psq', $password = 'Vincent7', $db = 'kanban');
    $params = json_decode($request->getBody());

    $item = $params->item;

    $db->query("select item, rack from item_racks where item like '$item'");

    while($db->fetchObject()) {
        $result[] = array_map('utf8_encode', $db->row);
    }

    if(!isset($result)) {
        $result = array();
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});

$app->post('/item_on_hand', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $item = $params->item;

    $db->query("exec kb_on_hand '$item'");

    while($db->fetchObject()) {
        $result[] = array_map('utf8_encode', $db->row);
    }

    if(!isset($result)) {
        $result = array();
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});


$app->get('/get_project', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = $request->getQueryParams();
    $productionOrder = $params['order'];

    $res = $db->queryOne("select Project from LNProductionOrder where ProductionOrder = '$productionOrder'");

    echo json_encode(['project' => $res]);
});