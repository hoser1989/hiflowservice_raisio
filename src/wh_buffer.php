<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/wh_buffer', function (Request $request, Response $response, $args) {
    $db = new database();

    $params = json_decode($request->getBody());
    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $sortName = ($params->sortName) ? $params->sortName: null;
    $sortOrder = ($params->sortOrder) ? $params->sortOrder: null;
    $newFilterValue = json_decode($filterValue, true);
    $from = $params->from;
    $to = $params->to;

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'StartDate' || $k == 'picking_start' || $k == 'staged_time' || $k == 'delivery_time') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                if($i == 0) {
                    $whereFilterQuery .=  ' cast('.$k . ' as date) ' . $comparator . " ''" . substr($date, 0,10) . "''";
                } else {
                    $whereFilterQuery .= ' and cast('. $k . ' as date) ' . $comparator . " ''" . substr($date, 0, 10) . "''";
                }
                $i++;
            } else {
                if($i == 0) {
                    $whereFilterQuery .=  $k . " like ''%" . $v['value'] ."%''";
                } else if ($i < $nfv_count - 1) {
                    $whereFilterQuery .= " and ". $k . " like ''%" . $v['value'] ."%''";
                } else if ($i == $nfv_count - 1){
                    $whereFilterQuery .= " and " . $k . " like ''%" . $v['value'] . "%''";
                }
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    if($sortName != null and $sortOrder != null) {
        $whereSort = "[$sortName] $sortOrder";
    } else {
        $whereSort = 'pdno';
    }

    $whereFromTo = "rn >= $from and rn <= $to";

    $db->query("exec wh_b_generate_view_buffer '$whereFilterQuery', '$whereSort', '$whereFromTo'");

    while($db->fetchObject()) {
        if(isset($db->row['StartDate'])) {
            $freeBufferDate = $db->row["StartDate"];
            $db->row['StartDate'] =  $freeBufferDate->format('Y-m-d H:i:s');
        }
        if(isset($db->row['picking_start'])) {
            $freeBufferDate = $db->row["picking_start"];
            $db->row['picking_start'] =  $freeBufferDate->format('Y-m-d H:i:s');
        }
        if (isset($db->row["staged_time"])) {
            $dateDuringPicking = $db->row["staged_time"];
            $db->row['staged_time'] = $dateDuringPicking->format('Y-m-d H:i:s');
        }
        if (isset($db->row["delivery_time"])) {
            $dateDuringPicking = $db->row["delivery_time"];
            $db->row['delivery_time'] = $dateDuringPicking->format('Y-m-d H:i:s');
        }
        $result[] = array_map('utf8_encode',$db->row);
    }

    if(!isset($result)) {
        $result = array(array('rn' => " ",
            'pdno' => 'No data',
            'StartDate' => 'No data',
            'cwoc' => 'No data',
            'zone' => 'No data',
            'login_assigned' => 'No data',
            'status' => 'No data',
            'picking_start' => 'No data',
            'staged_time' => 'No data',
            'delivery_time' => 'No data',
            'lines' => 'No data'
        ));
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});


$app->post('/wh_buffer_count', function (Request $request, Response $response, $args) {
    $db = new database();

    $params = json_decode($request->getBody());
    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'StartDate' || $k == 'picking_start' || $k == 'staged_time' || $k == 'delivery_time') {
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

    $res = $db->queryOne("select count (*) from (
                  select w.pdno,
                         w.cwoc,
                         w.zone,
                         w.login_assigned,
                         w.picking_start,
                         case w.status
                             when 1 then 'Nowe'
                             when 2 then 'W trakcie'
                             when 3 then 'Odlozone'
                             when 4 then 'Dostarczone'
                             end
                             as [status],
                         w.buffer_location,
                         w.staged_time,
                         w.delivery_time,
                         w.lines
                  from wh_buffer w
                           left join LNProductionOrder wo on wo.ProductionOrder = w.pdno
              ) wh_b
               where $whereFilterQuery");

    echo json_encode(['count' => $res]);
});

$app->post('/wh_buffer_locations', function (Request $request, Response $response, $args) {
    $db = new database();

    $db->query("select * from dict_wh_buffer_locations");

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

$app->post('/wh_buffer_start_picking', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;
    $zone = $params->zone;
    $zoneId = $params->zoneId;

    $db = new database();

    $db->query("exec wh_b_start_picking '$productionOrder', '$workCenter', '$zone', $zoneId");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/wh_buffer_put_away', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;
    $zone = $params->zone;
    $zoneId = $params->zoneId;
    $loca = $params->location;

    $db = new database();

    $db->query("exec wh_b_put_away '$productionOrder', '$workCenter', '$zone', '$loca'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/check_wh_buffer_put_away', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;
    $zone = $params->zone;

    $db = new database();

    $db->query("exec wh_b_check_put_away '$productionOrder', '$workCenter', '$zone'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});