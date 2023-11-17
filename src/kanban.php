<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post('/kb_work_centers', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("exec kb_show_wc");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(!isset($result)) {
        $result = array();
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});

$app->post('/kb_items', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $wc = isset($params->wc) ? $params->wc : null;

    $db = new database();
    $db->query("exec kb_show_wc_item '$wc'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(!isset($result)) {
        $result = array();
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});

$app->post('/kb_request', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $wc = isset($params->wc) ? $params->wc : 0;
    $item = isset($params->item) ? $params->item : 0;
    $zoneId = isset($params->user) ? $params->user: null;

    $db = new database();
    $db->query("exec kb_add_request '$wc', '$item', $zoneId");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});

$app->post('/kb_requests_table', function (Request $request, Response $response, $args) {
    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'dt_called' || $k == 'dt_start_picking' || $k == 'dt_delivered') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                $fk = $db->changeKanbanColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .=  ' cast('.$fk . ' as date) ' . $comparator . " '" . substr($date, 0,10) . "'";
                } else {
                    $whereFilterQuery .= ' and cast('. $fk . ' as date) ' . $comparator . " '" . substr($date, 0, 10) . "'";
                }
                $i++;
            } else {
                if($v['value'] == 1) {
                    $whereFilterQuery = 'kr.status in (1,2)';
                } else {
                    $fk = $db->changeKanbanColumnName($k);
                    if($i == 0) {
                        $whereFilterQuery .=  $fk . " like '%" . $v['value'] ."%'";
                    } else if ($i < $nfv_count - 1) {
                        $whereFilterQuery .= " and ". $fk . " like '%" . $v['value'] ."%'";
                    } else if ($i == $nfv_count - 1){
                        $whereFilterQuery .= " and " . $fk . " like '%" . $v['value'] . "%'";
                    }
                }
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $searchValue = ($params->searchValue !== null) ? $params->searchValue : null;

    $searchValueQuery = "(kr.item like '%$searchValue%' or
                        kr.who_called like '%$searchValue%' or
                        kr.dt_called like '%$searchValue%')";


    if($searchValue != null  and $filterValue != null) {
        $whereFilter = $searchValueQuery . ' and ' . $whereFilterQuery;
    } else if ($searchValue != null) {
        $whereFilter = $searchValueQuery;
    } else if ($filterValue != null) {
        $whereFilter = $whereFilterQuery;
    } else {
        $whereFilter = "1=1";
    }

    $sortName = ($params->sortName) ? $params->sortName: null;
    $sortOrder = ($params->sortOrder) ? $params->sortOrder: null;

    if($sortName != null and $sortOrder != null) {
        $whereSort = "kr.$sortName $sortOrder";
    } else {
        $whereSort = 'kr.dt_called';
    }

    $from = $params->from;
    $to = $params->to;
    $whereFromTo = "rn >= $from and rn <= $to";

   $db->query("select *
                    from (
                             select row_number() over (order by $whereSort) as rn,
                                    kr.id,
                                    kr.item,
                                    kr.dsca,
                                    kr.dt_called,
                                    kr.wc,
                                    kr.status,
                                    kr.who_called,
                                    kr.box_quan,
                                    kr.dt_start_picking,
                                    kr.dt_delivered,
                                    kr.who_delivered,
                                    kr.comment,
                                    kr.dt_cancelled,
                                    kr.who_cancelled
                             from dbo.kb_requests kr
                             where $whereFilter
                         )a where $whereFromTo
                    ");

    while($db->fetchObject()) {
        if (isset($db->row["dt_called"])) {
            $dateCalled = $db->row["dt_called"];
            $db->row['dt_called'] = $dateCalled->format('Y-m-d H:i:s');
        }
        if (isset($db->row["dt_start_picking"])) {
            $dtStartPicking = $db->row["dt_start_picking"];
            $db->row['dt_start_picking'] = $dtStartPicking->format('Y-m-d H:i:s');
        }
        if (isset($db->row["dt_delivered"])) {
            $dtDelivered = $db->row["dt_delivered"];
            $db->row['dt_delivered'] = $dtDelivered->format('Y-m-d H:i:s');
        }
        if (isset($db->row["dt_cancelled"])) {
            $dtDelivered = $db->row["dt_cancelled"];
            $db->row['dt_cancelled'] = $dtDelivered->format('Y-m-d H:i:s');
        }
        $result[] = array_map('utf8_encode',$db->row);
    }

    if(!isset($result)) {
        $result = array(array('rn' => " ",
            'id' => 'No data',
            'item' => 'No data',
            'dsca' => 'No data',
            'dt_called' => 'No data',
            'wc' => 'No data',
            'who_called' => 'No data',
            'box_quan' => 'No data',
            'dt_start_picking' => 'No data',
            'dt_delivered' => 'No data',
            'who_delivered' => 'No data',
            'comment' => 'No data'
        ));
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});

$app->post('/kb_request_table_count', function (Request $request, Response $response, $args) {
    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'dt_called' || $k == 'dt_start_picking' || $k == 'dt_delivered') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                $fk = $db->changeKanbanColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .=  ' cast('.$fk . ' as date) ' . $comparator . " '" . substr($date, 0,10) . "'";
                } else {
                    $whereFilterQuery .= ' and cast('. $fk . ' as date) ' . $comparator . " '" . substr($date, 0, 10) . "'";
                }
                $i++;
            } else {
                $fk = $db->changeKanbanColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .=  $fk . " like '%" . $v['value'] ."%'";
                } else if ($i < $nfv_count - 1) {
                    $whereFilterQuery .= " and ". $fk . " like '%" . $v['value'] ."%'";
                } else if ($i == $nfv_count - 1){
                    $whereFilterQuery .= " and " . $fk . " like '%" . $v['value'] . "%'";
                }
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $searchValue = ($params->searchValue !== null) ? $params->searchValue : null;

    $searchValueQuery = "(kr.item like '%$searchValue%' or
                        kr.who_called like '%$searchValue%' or
                        kr.dt_called like '%$searchValue%')";


    if($searchValue != null  and $filterValue != null) {
        $whereFilter = $searchValueQuery . ' and ' . $whereFilterQuery;
    } else if ($searchValue != null) {
        $whereFilter = $searchValueQuery;
    } else if ($filterValue != null) {
        $whereFilter = $whereFilterQuery;
    } else {
        $whereFilter = "1=1";
    }

    $userId = $params->userId;

    $res = $db->queryOne("  select count(*) from dbo.kb_requests kr where $whereFilter");

    echo json_encode(['count' => $res]);

});

$app->post('/kb_start_picking', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $id = $params->id;
    $zone = $params->zone;

    $db = new database();

    $db->query("exec kb_start_picking $id, $zone, '0'");

    echo json_encode(["status" => 'KB Picking Started!']);
});

$app->post('/kb_end_picking', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $id = $params->id;
    $zone = $params->zone;

    $db = new database();

    $db->query("exec kb_end_picking $id, $zone, '0'");

    echo json_encode(["status" => 'KB Picking Completed!']);
});

$app->post('/kb_comments', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $id = $params->id;

    $db->query("select id_request, replace(convert(varchar,dt,111), '/', '-') as dt, seq, comment, [name] from kb_comments where id_request = '$id'");

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

$app->post('/kb_add_comment', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $id = $params->id;
    $zoneId = $params->zoneId;
    $comment = $params->comment;

    $db->query("exec kb_add_comment $id, $zoneId,'". $db->convert_ins($comment). "'");

    echo json_encode(["status" => 'KB Comment Added!']);
});

$app->post('/kb_cancel_call', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $id = $params->id;
    $zoneId = $params->zoneId;

    $result = $db->queryOne("exec kb_cancell $id, $zoneId");

    $resultXML = json_encode(["status" => $result]);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});