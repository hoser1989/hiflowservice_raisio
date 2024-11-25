<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


$app->get('/checklist', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = $request->getQueryParams();
    $productionOrder = $params['order'];
    $workCenter = $params['work_center'] ? $params['work_center'] : '0';

    $db->query("select
                       qcl.id,
                       qcl.pdno,
                       qcl.cwoc,
                       qcl.pos,
                       qcl.activity,
                       qcl.type_of_activity,
                       qcl.drop_down_id,
                       qcl.value,
                       qcl.comment,
                       qcl.dt,
                       qcl.login,
                       qcl.status,
                       qcl.efdt,
                       qcl.exdt,
                       qcl.item
                from qa_check_list qcl
                where qcl.pdno = '$productionOrder' and qcl.cwoc = '$workCenter'
                order by qcl.pdno, qcl.cwoc, qcl.pos");

    $result = array();
    while($db->fetchObject()) {
        if (isset($db->row['dt'])) {
            $startDate = $db->row["dt"];
            $db->row['dt'] = $startDate->format('Y-m-d H:i:s');
        }
        if (isset($db->row['efdt'])) {
            $startDate = $db->row['efdt'];
            $db->row['efdt'] = $startDate->format('Y-m-d H:i:s');
        }
        if (isset($db->row['exdt'])) {
            $startDate = $db->row["exdt"];
            $db->row['exdt'] = $startDate->format('Y-m-d H:i:s');
        }
        $result[] = array_map('utf8_encode', $db->row);
    }
    $i = 0;
    foreach($result as $res) {
        if($res['type_of_activity'] === 'dl') {
            $drop_down_list = [];
            $drop_down_id = $res['drop_down_id'];
            $db->query("select * from qa_dl_lists_values where list_id = $drop_down_id");
            while($db->fetchObject()) {
                $drop_down_list[] = $db->row;
            }
            $result[$i]['drop_down_list'] = $drop_down_list;
        } else {
            $result[$i]['drop_down_list'] = [];
        }
        $i++;
    }

    if(!isset($result)) {
        $result = array();
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});


$app->post('/save_checklist_action', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $id = isset($params->id) ? $params->id : null;
    $comment = isset($params->comment) ? $params->comment : null;
    $value = isset($params->value) ? $params->value : '';
    $userName = isset($params->userName) ? $params->userName : null;
    $status = isset($params->status) ? $params->status : '';
    $efdt = isset($params->efdt) ? $params->efdt : null;

    $db = new database();
    $db->query("exec qa_update_checklist $id, '$comment', '$value', '$userName','$status', '$efdt'");

    echo json_encode(["status" => 'Action saved!']);

});

$app->post('/save_checklist_all', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $checklist = isset($params->checklist) ? $params->checklist : null;
    $userName = isset($params->userName) ? $params->userName : null;
    $productionOrder = isset($params->productionOrder) ? $params->productionOrder : null;
    $workCenter = isset($params->workCenter) ? $params->workCenter : null;

    $db = new database();
    $db->query("select * from qa_check_list where pdno = '$productionOrder' and cwoc = '$workCenter' order by pos");

    while($db->fetchObject()) {
        if (isset($db->row['dt'])) {
            $startDate = $db->row["dt"];
            $db->row['dt'] = $startDate->format('Y-m-d H:i:s');
        }
        if (isset($db->row['efdt'])) {
            $startDate = $db->row["efdt"];
            $db->row['efdt'] = $startDate->format('Y-m-d H:i:s');
        }
        if (isset($db->row['exdt'])) {
            $startDate = $db->row["exdt"];
            $db->row['exdt'] = $startDate->format('Y-m-d H:i:s');
        }
        $currentChecklist[] = array_map('utf8_encode',$db->row);
    }

    $convChecklist = [];
    foreach ($checklist as $obj) {
        $convChecklist[] = (array) $obj;
    }

    $i = 0;
    $results = array();
    foreach ($convChecklist as $arr) {
        if($arr['comment'] !== $currentChecklist[$i]['comment'] ||
            $arr['status'] !== $currentChecklist[$i]['status'] ||
            $arr['value'] !== $currentChecklist[$i]['value']) {
//            echo "<pre>";
//            echo 'Comment: ' . $arr['comment'] . " | ". $currentChecklist[$i]['comment'] . ' ^';
//            echo 'status: ' . $arr['status'] . " | ". $currentChecklist[$i]['status'] . ' ^';
//            echo 'value: ' . $arr['value'] . " | ". $currentChecklist[$i]['value'] . ' ^';
//            echo "</pre>";
            $res = $db->queryOne("exec qa_update_checklist '". $arr['id'] ."', '". $arr['comment'] ."', '". $arr['value'] ."', '$userName','". $arr['status']."', '". $arr['efdt'] ."'");
            $results[$arr['id']] = $res;
        }
        $i++;
    }


    echo json_encode(["status" => $results]);

});
