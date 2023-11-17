<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/production_losses_table', function (Request $request, Response $response, $args) {
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

    $searchValueQuery = "(login_start like '%$searchValue%' or
                        login_end like '%$searchValue%' or
                        cwoc like '%$searchValue%' or
                        comment like '%$searchValue%')";


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
        $whereSort = 'dt_start';
    }

    $from = $params->from;
    $to = $params->to;
    $whereFromTo = "rn >= $from and rn <= $to";

    $db->query("select
                       *
                    from
                    (
                        select row_number() over (order by id) as rn, *
                            from (
                               select
                               ppl.id,
                               status,
                               dt_start,
                               login_start,
                               dt_end,
                               login_end,
                               dpwc.id_lev_1 as wc_id_lev_1,
                               dpwc.dsc_lev_1 as wc_dsc_lev_1,
                               dpwc.id_lev_2 as wc_id_lev_2,
                               dpwc.dsc_lev_2 as wc_dsc_lev_2,
                               dpwc.id_lev_3 as wc_id_lev_3,
                               dpwc.dsc_lev_3 as wc_dsc_lev_3,
                               dpr.id_lev_1 as reason_id_lev_1,
                               dpr.dsc_lev_1 as reason_dsc_lev_1,
                               dpr.id_lev_2 as reason_id_lev_2,
                               dpr.dsc_lev_2 as reason_dsc_lev_2,
                               dpr.id_lev_3 as reason_id_lev_3,
                               dpr.dsc_lev_3 as reason_dsc_lev_3,
                               employees,
                               shift,
                               comment,
                               datediff(minute, dt_start, dt_end) as 'loss'
                            from pl_production_losses ppl
                            left join dict_pl_reasons dpr on
                                           (dpr.id_lev_1 = ppl.id_lv1
                                            and dpr.id_lev_2 = ppl.id_lv2
                                            and dpr.id_lev_3 = ppl.id_lv3
                                             )
                            left join dict_pl_work_centers dpwc on
                                            (dpwc.id_lev_1 = ppl.id_cwoc1
                                             and dpwc.id_lev_2 = ppl.id_cwoc2
                                             and dpwc.id_lev_3 = ppl.id_cwoc3
                                            )
                        ) a
                        where $whereFilter
                    ) final where $whereFromTo");

    while($db->fetchObject()) {
        if (isset($db->row["dt_start"])) {
            $dateCalled = $db->row["dt_start"];
            $db->row['dt_start'] = $dateCalled->format('Y-m-d H:i:s');
        }
        if (isset($db->row["dt_end"])) {
            $dateCalled = $db->row["dt_end"];
            $db->row['dt_end'] = $dateCalled->format('Y-m-d H:i:s');
        }
        $result[] = array_map('utf8_encode',$db->row);
    }

    if(!isset($result)) {
        $result = array(array('rn' => " ",
            'id' => 'No data',
            'status' => 'No data',
            'dt_start' => 'No data',
            'login_start' => 'No data',
            'dt_end' => 'No data',
            'login_end' => 'No data',
            'cwoc' => 'No data',
            'dsc_lev_1' => 'No data',
            'dsc_lev_2' => 'No data',
            'dsc_lev_3' => 'No data',
            'employees' => 'No data',
            'shift' => 'No data',
            'comment' => 'No data'
        ));
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});


$app->post('/production_losses_table_count', function (Request $request, Response $response, $args) {
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

    $searchValueQuery = "(login_start like '%$searchValue%' or
                        login_end like '%$searchValue%' or
                        cwoc like '%$searchValue%' or
                        comment like '%$searchValue%')";


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
                                    select row_number() over (order by ppl.dt_start) as rn,
                                       ppl.id,
                                       status,
                                       dt_start,
                                       login_start,
                                       dt_end,
                                       login_end,
                                       dpwc.id_lev_1 as wc_id_lev_1,
                                       dpwc.dsc_lev_1 as wc_dsc_lev_1,
                                       dpwc.id_lev_2 as wc_id_lev_2,
                                       dpwc.dsc_lev_2 as wc_dsc_lev_2,
                                       dpwc.id_lev_3 as wc_id_lev_3,
                                       dpwc.dsc_lev_3 as wc_dsc_lev_3,
                                       dpr.id_lev_1 as reason_id_lev_1,
                                       dpr.dsc_lev_1 as reason_dsc_lev_1,
                                       dpr.id_lev_2 as reason_id_lev_2,
                                       dpr.dsc_lev_2 as reason_dsc_lev_2,
                                       dpr.id_lev_3 as reason_id_lev_3,
                                       dpr.dsc_lev_3 as reason_dsc_lev_3,
                                       employees,
                                       shift,
                                       comment,
                                       datediff(minute, dt_start, dt_end) as 'loss'
                                    from pl_production_losses ppl
                                    left join dict_pl_reasons dpr on
                                                   (dpr.id_lev_1 = ppl.id_lv1
                                                    and dpr.id_lev_2 = ppl.id_lv2
                                                    and dpr.id_lev_3 = ppl.id_lv3
                                                     )
                                    left join dict_pl_work_centers dpwc on
                                                    (dpwc.id_lev_1 = ppl.id_cwoc1
                                                     and dpwc.id_lev_2 = ppl.id_cwoc2
                                                     and dpwc.id_lev_3 = ppl.id_cwoc3
                                                    )
                                ) a where $whereFilter");

    echo json_encode(['count' => $res]);
});


$app->post('/pl_level1', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select distinct id_lev_1, dsc_lev_1 from dict_pl_reasons");

    while($db->fetchObject()) {
        $result[] = array_map('utf8_encode',$db->row);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/pl_level2', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $id1 = isset($params->level1Id) ? $params->level1Id : null;

    if($id1 != null ) {
        $db->query("select distinct id_lev_2, dsc_lev_2 from dict_pl_reasons where id_lev_1 = $id1");

        while($db->fetchObject()) {
            $result[] = array_map('utf8_encode',$db->row);
        }
    }

    if(empty($result)) {
        $result = array('id_lev_3' => '0', 'dsc_lev_3' => null);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/pl_level3', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $id1 = isset($params->level1Id) ? $params->level1Id : null;
    $id2 = isset($params->level2Id) ? $params->level2Id: null;

    if($id1 != null && $id2 != null) {
        $db->query("select distinct id_lev_3, dsc_lev_3 from dict_pl_reasons where id_lev_1 = $id1 and id_lev_2 = $id2");

        while ($db->fetchObject()) {
            $result[] = array_map('utf8_encode', $db->row);
        }
    }

    if (empty($result)) {
        $result = array('id_lev_3' => '0', 'dsc_lev_3' => null);
    }


    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});

$app->post('/add_loss', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    if(isset($params->wc1) != false) {
        $wc1 = is_null($params->wc1) ? 0 : $params->wc1;
    } else {
        $wc1 = 0;
    }
    if(isset($params->wc2) != false) {
        $wc2 = is_null($params->wc2) ? 0 : $params->wc2;
    } else {
        $wc2 = 0;
    }
    if(isset($params->wc3) != false ) {
        $wc3 = is_null($params->wc3) ? 0 : $params->wc3;
    } else {
        $wc3 = 0;
    }
    if(isset($params->level1) != false) {
        $level1 = is_null($params->level1) ? 0 : $params->level1;
    } else {
        $level1 = 0;
    }
    if(isset($params->level2) != false) {
        $level2 = is_null($params->level2) ? 0 : $params->level2;
    } else {
        $level2 = 0;
    }
    if(isset($params->level3) != false ) {
        $level3 = is_null($params->level3) ? 0 : $params->level3;
    } else {
        $level3 = 0;
    }
    if(isset($params->comment) != false) {
        $comment_to_check = is_null($params->comment) ? '' : $params->comment;
        if(preg_match( "/'/",$comment_to_check)) {
            $comment = str_replace("'", "''",$comment_to_check);
        } else {
            $comment = $comment_to_check;
        }
    } else {
        $comment = '';
    }
    if(isset($params->employees) != false) {
        $employees = (is_null($params->employees) || $params->employees == '') ?  '0' : $params->employees;
    } else {
        $employees = 0;
    }
    if(isset($params->shift) != false) {
        $shift = (is_null($params->shift) || $params->shift == '') ?  '0' : $params->shift;
    } else {
        $shift = 0;
    }

    $user = $params->user;
    $commentINS = $db->convert_ins($comment);

    if(isset($params->startDate) != false) {
        $startDate = (is_null($params->startDate) || $params->startDate == '') ?  '' : $params->startDate;
    } else {
        $startDate = '';
    }

    if($wc1 != 0 && $wc2 != 0 && $wc3 != 0 && $level1 != 0 && $level2 != 0 && $employees != 0 && $shift != 0) {
        $res = $db->queryOne("exec pl_add_line $wc1, $wc2, $wc3, $user, $level1, $level2, $level3, '$commentINS',$employees, $shift, '$startDate'");
        echo json_encode(["status" => "$res"]);
    } else {
        echo json_encode(['status' => 'Sprawdz']);
    }

});

$app->post('/end_loss', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $id = $params->id;
    $zoneId = $params->zoneId;
    $data = ''; //$params->data;

    $result = $db->queryOne("exec pl_close_line $id, $zoneId, '$data'");

    $resultXML = json_encode(["status" => $result]);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/pl_wc_level1', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select distinct id_lev_1, dsc_lev_1 from dict_pl_work_centers");

    while($db->fetchObject()) {
        $result[] = array_map('utf8_encode',$db->row);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/pl_wc_level2', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $id1 = isset($params->level1Id) ? $params->level1Id : null;

    if($id1 != null ) {
        $db->query("select distinct id_lev_2, dsc_lev_2 from dict_pl_work_centers where id_lev_1 = $id1");

        while($db->fetchObject()) {
            $result[] = array_map('utf8_encode',$db->row);
        }
    }

    if(empty($result)) {
        $result = array('id_lev_2' => '0', 'dsc_lev_2' => null);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/pl_wc_level3', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $id1 = isset($params->level1Id) ? $params->level1Id : null;
    $id2 = isset($params->level2Id) ? $params->level2Id: null;

    if($id1 != null && $id2 != null) {
        $db->query("select distinct id_lev_3, dsc_lev_3 from dict_pl_work_centers where id_lev_1 = $id1 and id_lev_2 = $id2");

        while ($db->fetchObject()) {
            $result[] = array_map('utf8_encode', $db->row);
        }
    }

    if (empty($result)) {
        $result = array('id_lev_3' => '0', 'dsc_lev_3' => null);
    }


    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});