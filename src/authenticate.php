<?php

require_once ('./lib/auth.php');
require_once ('./lib/database.php');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post('/authenticate', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $login = $params->login;
    $pass = $params->pass;
    $cardId = $params->cardId;

    $auth = new auth();
    $access = $auth->login($login, $pass, $cardId );
    echo $access;

});


$app->post('/get_user_details', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $login = $params->login;
    $cardId = $params->cardId;
    $db = new database();
    if ($login != null) {
        $db->query("exec get_user_details '$login'");
    } else if ($cardId != null) {
        $db->query("exec get_user_details '$cardId'");
    }

    $result = array();
    while($db->fetch()) {
        $result[] = $db->row;
    }
    $db->close();
    return json_encode($result);

});

$app->post('/foreman_authenticate', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $zone_id = $params->zone_id;

    $db = new Database($host = 'Plsts1-s0044', $user = 'psq', $password = 'Vincent7', $db = 'hiabworkers');
    if($zone_id != null) {
        $res = $db->queryOne("select hiflow_foreman from workers where zone_id = $zone_id");
    } else {
        $res = 2;
    }

    return json_encode($res);
});