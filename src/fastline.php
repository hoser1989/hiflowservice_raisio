<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/fastline_queue', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'fb_dt') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                $fk = $db->changeFastLineColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .=  ' cast('.$fk . ' as date) ' . $comparator . " ''" . substr($date, 0,10) . "''";
                } else {
                    $whereFilterQuery .= ' and cast('. $fk . ' as date) ' . $comparator . " ''" . substr($date, 0, 10) . "''";
                }
                $i++;
            } else {
                $fk = $db->changeFastLineColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .=  $fk . " like ''%" . $v['value'] ."%''";
                } else if ($i < $nfv_count - 1) {
                    $whereFilterQuery .= " and ". $fk . " like ''%" . $v['value'] ."%''";
                } else if ($i == $nfv_count - 1){
                    $whereFilterQuery .= " and " . $fk . " like ''%" . $v['value'] . "%''";
                }
                $i++;
            }
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $sortName = ($params->sortName) ? $params->sortName: null;
    $sortOrder = ($params->sortOrder) ? $params->sortOrder: null;

    if($sortName != null and $sortOrder != null) {
        $whereSort = "$sortName $sortOrder";
    } else {
        $whereSort = 'fb_dt asc';
    }

    $from = $params->from;
    $to = $params->to;
    $whereFromTo = "rn >= $from and rn <= $to";

    $db->query("exec generate_view_wh_fast_line '$whereFilterQuery', '$whereSort', '$whereFromTo'");

    while($db->fetchObject()) {
        if(isset($db->row['fb_dt'])) {
            $freeBufferDate = $db->row["fb_dt"];
            $db->row['fb_dt'] =  $freeBufferDate->format('Y-m-d H:i:s');
        }
        $result[] = array_map('utf8_encode',$db->row);
    }

    if(!isset($result)) {
        $result = array(array('rn' => " ",
            'pdno' => 'No data',
            'cwoc' => 'No data',
            'missings' => 'No data',
            'fb_dt' => 'No data',
            'status' => 'No data',
            'user_name' => 'No data',
        ));
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});

$app->post('/fastline_queue_count', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'fb_dt') {
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
                $i++;
            }
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $res = $db->queryOne("exec generate_view_wh_fast_line_count '$whereFilterQuery'");

    echo json_encode(['count' => $res]);
});

$app->post('/show_missings', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = trim($params->productionOrder);
    $workCenter = $params->workCenter;

    $db = new database();
    $db->query("exec wh_display_fast_line_details '$workCenter','$productionOrder'");

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

$app->post('/change_fastline_status', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = trim($params->productionOrder);
    $workCenter = $params->workCenter;
    $zoneId = $params->zoneId;

    $db = new database();
    $db->query("exec wh_change_fast_line_status '$workCenter','$productionOrder',$zoneId");

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