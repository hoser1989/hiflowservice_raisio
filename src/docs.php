<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


$app->post('/docs', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $wc = $params->wc;
//    $wc = 'D_S1.5LR';
    $db->query("exec generate_docs_by_wc '$wc'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(isset($result)) {
        $res = $db->convert_arr_with_pic_to_json_original_size($result);
        $response->getBody();
        $response->write($res);
    } else {
        $resultXML = json_encode(['status' => 0]);
        $response->getBody();
        $response->write($resultXML);
    }

    return $response;
});

$app->post('/single_doc', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $streamID = $params->streamId;

    $db->query("exec get_doc_by_stream_id '$streamID'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(isset($result)) {
        $res = $db->convert_arr_with_pic_to_json_original_size($result);
        $response->getBody();
        $response->write($res);
    } else {
        $resultXML = json_encode(['status' => 0]);
        $response->getBody();
        $response->write($resultXML);
    }

    return $response;
});