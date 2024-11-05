<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app->post('/add_to_pre_engrave', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $productionOrder = isset($params->productionOrder) ? $params->productionOrder : null;
    $item = isset($params->item) ? $params->item : null;
    $dsca = isset($params->dsca) ? $params->dsca : null;
    $serialNumber = isset($params->serialNumber) ? $params->serialNumber : null;
    $zoneId = isset($params->zoneId) ? $params->zoneId : null;

    $db = new database();
    $db->query("exec add_to_pre_do_grawerowania '$productionOrder', '$item', '$dsca', '$serialNumber', $zoneId ");

    echo json_encode(["status" => 'Added to pre engrave!']);

});


$app->post('/pre_engraving_table', function (Request $request, Response $response, $args) {
    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'dt_called' || $k == 'dt_sent') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                if($i == 0) {
                    $whereFilterQuery .=  ' cast('.$k . ' as date) ' . $comparator . " '" . substr($date, 0,10) . "'";
                } else {
                    $whereFilterQuery .= ' and cast('. $k . ' as date) ' . $comparator . " '" . substr($date, 0, 10) . "'";
                }
                $i++;
            } else {
                if($i == 0) {
                    $whereFilterQuery .=  $k . " like '%" . $v['value'] ."%'";
                } else if ($i < $nfv_count - 1) {
                    $whereFilterQuery .= " and ". $k . " like '%" . $v['value'] ."%'";
                } else if ($i == $nfv_count - 1){
                    $whereFilterQuery .= " and " . $k . " like '%" . $v['value'] . "%'";
                }
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $searchValue = ($params->searchValue !== null) ? $params->searchValue : null;

    $searchValueQuery = "(ProductionOrder like '%$searchValue%' or
                        ItemNo like '%$searchValue%' or
                        Description like '%$searchValue%' or
                        SerialNumber like '%$searchValue%')";


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
        $whereSort = "$sortName $sortOrder";
    } else {
        $whereSort = 'dt_called';
    }

    $from = $params->from;
    $to = $params->to;
    $whereFromTo = "rn >= $from and rn <= $to";

    $db->query("select *
                    from (
                        select row_number() over (order by $whereSort) as rn,
                            *
                        from pre_do_grawerowania
                        where $whereFilter
                    ) a
                    where $whereFromTo");

    while($db->fetchObject()) {
        if (isset($db->row["dt_called"])) {
            $dateCalled = $db->row["dt_called"];
            $db->row['dt_called'] = $dateCalled->format('Y-m-d H:i:s');
        }
        if (isset($db->row["dt_sent"])) {
            $dateCalled = $db->row["dt_sent"];
            $db->row['dt_sent'] = $dateCalled->format('Y-m-d H:i:s');
        }
        $result[] = array_map('utf8_encode',$db->row);
    }

    if(!isset($result)) {
        $result = array(array('rn' => " ",
            'id' => 'No data',
            'ProductionOrder' => 'No data',
            'ItemNo' => 'No data',
            'Description' => 'No data',
            'SerialNumber' => 'No data',
            'gr_status' => 'No data',
            'ce_item' => 'No data',
            'ce_type' => 'No data',
            'who_called' => 'No data',
            'dt_called' => 'No data',
            'who_sent_to_printer' => 'No data',
            'dt_sent' => 'No data'
        ));
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});


$app->post('/pre_engraving_table_count', function (Request $request, Response $response, $args) {
    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'dt_called' || $k == 'dt_sent') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                if($i == 0) {
                    $whereFilterQuery .=  ' cast('.$k . ' as date) ' . $comparator . " '" . substr($date, 0,10) . "'";
                } else {
                    $whereFilterQuery .= ' and cast('. $k . ' as date) ' . $comparator . " '" . substr($date, 0, 10) . "'";
                }
                $i++;
            } else {
                if($i == 0) {
                    $whereFilterQuery .=  $k . " like '%" . $v['value'] ."%'";
                } else if ($i < $nfv_count - 1) {
                    $whereFilterQuery .= " and ". $k . " like '%" . $v['value'] ."%'";
                } else if ($i == $nfv_count - 1){
                    $whereFilterQuery .= " and " . $k . " like '%" . $v['value'] . "%'";
                }
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $searchValue = ($params->searchValue !== null) ? $params->searchValue : null;

    $searchValueQuery = "(ProductionOrder like '%$searchValue%' or
                        ItemNo like '%$searchValue%' or
                        Description like '%$searchValue%' or
                        SerialNumber like '%$searchValue%')";


    if($searchValue != null  and $filterValue != null) {
        $whereFilter = $searchValueQuery . ' and ' . $whereFilterQuery;
    } else if ($searchValue != null) {
        $whereFilter = $searchValueQuery;
    } else if ($filterValue != null) {
        $whereFilter = $whereFilterQuery;
    } else {
        $whereFilter = "1=1";
    }

    $res = $db->queryOne("select count(*)
                    from (
                        select row_number() over (order by dt_called) as rn,
                            *
                        from pre_do_grawerowania
                        where $whereFilter
                    ) a");

    echo json_encode(['count' => $res]);
});


$app->post('/engrave_send_to_printer', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $zoneId = isset($params->zoneId) ? $params->zoneId : null;
    $id = isset($params->id) ? $params->id : null;
    $clearQueue = isset($params->clearQueue) ? $params->clearQueue : null;

    $db = new database();
    $db->query("exec add_to_do_grawerowania $zoneId, $id, $clearQueue");

    echo json_encode(["status" => 'Sent to printer!']);

});

$app->post('/engrave_cancel_call', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $id = $params->id;
    $zoneId = $params->zoneId;

    $result = $db->queryOne("exec pre_engrave_cancel $id, $zoneId");

    $resultXML = json_encode(["status" => $result]);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/engrave_check_the_printer_queue', function (Request $request, Response $response, $args) {
    $db = new database();

    $db->query("select ProductionOrder, SerialNumber from do_grawerowania where status = 0 and 1=1");

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