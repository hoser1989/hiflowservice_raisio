<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/picking_queue', function (Request $request, Response $response, $args) {
    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'free_buffer_date' || $k == 'date_during_picking' || $k == 'date_picked' || $k == 'StartDate' || $k == 'verification_dt') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                $fk = $db->changeWarehouseColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .=  ' cast('.$fk . ' as date) ' . $comparator . " '" . substr($date, 0,10) . "'";
                } else {
                    $whereFilterQuery .= ' and cast('. $fk . ' as date) ' . $comparator . " '" . substr($date, 0, 10) . "'";
                }
                $i++;
            } else {
                $fk = $db->changeWarehouseColumnName($k);
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

    $searchValueQuery = "(wpq.pdno like '%$searchValue%' or
                        wpq.cwoc like '%$searchValue%' or
                        wpq.zone like '%$searchValue%' or
                        wpq.nr_table like '%$searchValue%' or
                        wpq.free_buffer_date like '%$searchValue%' or
                        wpq.[login] like '%$searchValue%')";


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
        $whereSort = "wpq.$sortName $sortOrder";
    } else {
        $whereSort = 'convert(NVARCHAR(20),wpq.free_buffer_date,23), isnull(dwd.picking_time - datediff(minute, wpq.free_buffer_date, getdate()),999)';
    }

    $from = $params->from;
    $to = $params->to;
    $whereFromTo = "rn >= $from and rn <= $to";

    $userId = $params->userId;

// select picking queue partition by work center order by free buffer date - this query uses wh_users_top
//    $db->query("select *
//                        from (
//                                 select row_number() over (order by cwoc) as rn, *
//                                 from (
//                                            select dense_rank()
//                                                         over (order by $whereSort) as rnbycwoc,
//                                                 wpq.pdno,
//                                                 lp.StartDate,
//                                                 wpq.cwoc,
//                                                 wpq.zone,
//                                                 wpq.nr_table,
//                                                 wpq.free_buffer_date,
//                                                 wpq.free_buffer_login,
//                                                 wpq.status_during_picking,
//                                                 wpq.date_during_picking,
//                                                 wpq.status_picked,
//                                                 wpq.date_picked,
//                                                 wpq.[login],
//                                                 c.comment
//                                          from wh_picking_queue wpq
//                                                   left join LNProductionOrder lp on lp.ProductionOrder = wpq.pdno
//                                                   left join max_comment  mc on mc.pdno = wpq.pdno
//                                                   left join comments c on c.id = mc.id_max_comment
//                                          where $whereFilter
//                                            and cwoc in (
//                                              select duvw.wc_query
//                                              FROM users_view_wc_wh_picking_queue uvwwwp
//                                                       INNER JOIN
//                                                   dict_users_view_wc duvw ON duvw.id = uvwwwp.wc_id
//                                              WHERE uvwwwp.user_id = $userId
//                                          )
//                                      ) a
//                                 where rnbycwoc <= (select [top] from wh_users_top where user_id = $userId)) b
//                        where $whereFromTo");

    $db->query("select * from (
                        select row_number()
                                     over (order by $whereSort) as rn,
                             wpq.pdno,
                             lp.Description,
                             lp.StartDate,
                             wpq.cwoc,
                             lp.prod_line,
                             wpq.zone,
                             bf.location as buffer_location,  
                             wpq.nr_table,
                             wpq.free_buffer_date,
                             wpq.free_buffer_login,
                             wpq.status_during_picking,
                             wpq.date_during_picking,
                             wpq.status_picked,
                             wpq.date_picked,
                             wpq.[login],
                             c.comment,
                             dwd.picking_time,
                             dwd.picking_time - datediff(minute, wpq.free_buffer_date, getdate()) as countdown,
                             case wpq.verification_status
                                 when 1 then 'Tak'
                                 else 'Nie'
                             END as verification_status,
                             wpq.verification_login,
                             wpq.verification_dt,
                             wpq.project
                      from wh_picking_queue wpq
                               left join LNProductionOrder lp on lp.ProductionOrder = wpq.pdno
                               left join max_comment  mc on mc.pdno = wpq.pdno
                               left join comments c on c.id = mc.id_max_comment
                               left join dict_wc_dt dwd on dwd.wc = wpq.cwoc
                               left join wh_buffer_locations bf on (bf.pdno = wpq.pdno and  'D_' + bf.cwoc = wpq.cwoc and wpq.[zone] = bf.[zone])
                      where $whereFilter
                        and wpq.cwoc in (
                          select duvw.wc_query
                          FROM users_view_wc_wh_picking_queue uvwwwp
                                   INNER JOIN
                               dict_users_view_wc duvw ON duvw.id = uvwwwp.wc_id
                          WHERE uvwwwp.user_id = $userId))a
                          where $whereFromTo");

    while($db->fetchObject()) {
        if(isset($db->row['StartDate'])) {
            $freeBufferDate = $db->row["StartDate"];
            $db->row['StartDate'] =  $freeBufferDate->format('Y-m-d H:i:s');
        }
        if(isset($db->row['free_buffer_date'])) {
            $freeBufferDate = $db->row["free_buffer_date"];
            $db->row['free_buffer_date'] =  $freeBufferDate->format('Y-m-d H:i:s');
        }
        if (isset($db->row["date_during_picking"])) {
            $dateDuringPicking = $db->row["date_during_picking"];
            $db->row['date_during_picking'] = $dateDuringPicking->format('Y-m-d H:i:s');
        }
        if (isset($db->row["date_picked"])) {
            $datePicked = $db->row["date_picked"];
            $db->row['date_picked'] = $datePicked->format('Y-m-d H:i:s');
        }
        if (isset($db->row['cwoc'])) {
            $len = strlen($db->row['cwoc']);
            $db->row['cwoc'] = substr($db->row['cwoc'], 2, $len);
        }
        if (isset($db->row["verification_dt"])) {
            $verifyDate = $db->row["verification_dt"];
            $db->row['verification_dt'] = $verifyDate->format('Y-m-d H:i:s');
        }
        $result[] = array_map('utf8_encode',$db->row);
    }

    if(!isset($result)) {
        $result = array(array('rn' => " ",
            'pdno' => 'No data',
            'StartDate' => 'No data',
            'cwoc' => 'No data',
            'zone' => 'No data',
            'free_buffer_date' => 'No data',
            'status_during_picking' => 'No data',
            'date_during_picking' => 'No data',
            'status_picked' => 'No data',
            'date_picked' => 'No data',
            'login' => 'No data',
            'status' => 'No data',
            'comment' => 'No data',
            'verification_status' => 'No data',
            'verification_login' => 'No data',
            'verification_dt' => 'No data',
            'project' => 'No data'
        ));
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});


$app->post('/picking_queue_count', function (Request $request, Response $response, $args) {
    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'free_buffer_date' || $k == 'date_during_picking' || $k == 'date_picked') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                $fk = $db->changeWarehouseColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .=  ' cast('.$fk . ' as date) ' . $comparator . " '" . substr($date, 0,10) . "'";
                } else {
                    $whereFilterQuery .= ' and cast('. $fk . ' as date) ' . $comparator . " '" . substr($date, 0, 10) . "'";
                }
                $i++;
            } else {
                $fk = $db->changeWarehouseColumnName($k);
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

    $searchValueQuery = "(wpq.pdno like '%$searchValue%' or
                        wpq.cwoc like '%$searchValue%' or
                        wpq.zone like '%$searchValue%' or
                        wpq.nr_table like '%$searchValue%' or
                        wpq.free_buffer_date like '%$searchValue%' or
                        wpq.[login] like '%$searchValue%')";


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

//    $res = $db->queryOne("select count(*)
//                        from (
//                                 select row_number() over (order by cwoc) as rn, *
//                                 from (
//                                          select row_number()
//                                                         over ( partition by wpq.cwoc
//                                                             order by wpq.free_buffer_date, wpq.cwoc) as rnbycwoc,
//                                                 wpq.pdno,
//                                                 lp.StartDate,
//                                                 wpq.cwoc,
//                                                 wpq.zone,
//                                                 wpq.nr_table,
//                                                 wpq.free_buffer_date,
//                                                 wpq.free_buffer_login,
//                                                 wpq.status_during_picking,
//                                                 wpq.date_during_picking,
//                                                 wpq.status_picked,
//                                                 wpq.date_picked,
//                                                 wpq.[login],
//                                                 c.comment
//                                          from wh_picking_queue wpq
//                                                   left join LNProductionOrder lp on lp.ProductionOrder = wpq.pdno
//                                                   left join max_comment  mc on mc.pdno = wpq.pdno
//                                                   left join comments c on c.id = mc.id_max_comment
//                                          where $whereFilter
//                                            and cwoc in (
//                                              select duvw.wc_query
//                                              FROM users_view_wc_wh_picking_queue uvwwwp
//                                                       INNER JOIN
//                                                   dict_users_view_wc duvw ON duvw.id = uvwwwp.wc_id
//                                              WHERE uvwwwp.user_id = $userId
//                                          )
//                                      ) a
//                                 where rnbycwoc <= (select [top] from wh_users_top where user_id = $userId)) b");

    $res = $db->queryOne("select count(*)
                      from wh_picking_queue wpq
                               left join LNProductionOrder lp on lp.ProductionOrder = wpq.pdno
                               left join max_comment  mc on mc.pdno = wpq.pdno
                               left join comments c on c.id = mc.id_max_comment
                               left join dict_wc_dt dwd on dwd.wc = wpq.cwoc
                      where $whereFilter
                        and cwoc in (
                          select duvw.wc_query
                          FROM users_view_wc_wh_picking_queue uvwwwp
                                   INNER JOIN
                               dict_users_view_wc duvw ON duvw.id = uvwwwp.wc_id
                          WHERE uvwwwp.user_id = $userId)");

    echo json_encode(['count' => $res]);

});

$app->post('/start_picking', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = trim($params->productionOrder);
    $workCenter = $params->workCenter;
    $zone = $params->zone;
    $user = $params->user;

    $db = new database();
    $db->query("exec wh_start_picking '$productionOrder', 'D_$workCenter', '$zone', '$user'");

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

$app->post('/picking_completed', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;
    $zone = $params->zone;
    $user = $params->user;

    $db = new database();

    $db->query("exec wh_picking_completed '$productionOrder', 'D_$workCenter', '$zone', '$user'");

    echo json_encode(["status" => 'Picking Completed!']);
});

$app->post('/check_table', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $workCenter = $params->workCenter;

    $db = new database();
    $res = $db->queryOne("select count(*) from dict_tables_by_wc where cwoc = '$workCenter'");
    if($res > 0) {
        echo json_encode(["status" => '1']);
    } else {
        echo json_encode(["status" => '0']);
    }
});


$app->get('/tables_by_wc', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $workCenter = $params['wc'];

    $db = new database();
    $db->query("select * from dict_tables_by_wc where cwoc = '$workCenter'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(!isset($result)) {
        $res = array('cwoc' => "No data", 'nr_table' => 'No data');
        $result = array($res);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});


$app->post('/add_to_picking_queue', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;
    $state = $params->state;
    $table = $params->table;
    $zoneId = $params->zoneId;
    $prodLine = $params->prodLine;

    $db = new database();

    $db->query("exec update_max_state_date '$productionOrder', '$workCenter', $state, '$table',$zoneId, '$prodLine'");

    echo json_encode(["status" => '1']);
});


$app->post('/pl_check_zones', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;
    $db = new database();

    $db->query("select zone from wh_picking_queue where pdno = '$productionOrder' and cwoc = '$workCenter' and (verification_status is null or verification_status = 0)");

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

$app->post('/pl_check_status', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;
    $db = new database();

    $response = $db->queryOne("exec [pl_check_if_to_verify] '$productionOrder' ,'$workCenter'");

    echo json_encode(["status" => "$response"]);
});

$app->post('/verify_pl', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;
    $login = $params->login;
    $zone = $params->zone;
    $verifyStatus = $params->verifyStatus;

    $db = new database();

    $db->query("exec wh_verify_pl '$productionOrder', '$workCenter',  '$zone', $login, $verifyStatus");

    echo json_encode(["status" => '1']);
});