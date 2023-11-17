<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/view_by_user', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $user = $params->zoneId;

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        $j = 0;
        $whereFilterQueryWc = '';
        foreach($newFilterValue as $k => $v) {
            if($k == 'Start Date' || $k == 'Planned Time' || $k == 'Release Date' || $k == 'Effective Date') {
                $date = substr($v['value']['date'],0,10);
                $comparator = $v['value']['comparator'];
                $fk = $db->changeColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .= $fk . ' ' . $comparator . " ''" . $date. "''";
                } else {
                    $whereFilterQuery .= ' and '. $fk . ' ' . $comparator . " ''" . $date . "''";
                }
                $i++;
            } else if (substr($k, 0, 2) == "D_") {
                if($j == 0) {
                    $whereFilterQueryWc = "[".$k . "] = ''" . $v['value'] . "''";
                } else {
                    $whereFilterQueryWc .= " and [" . $k . "] = ''" . $v['value'] . "''";
                }
                $j++;
            } else if ($k === 'prod_seq') {
                $number = $v['value']['number'];
                $comparator = $v['value']['comparator'];
                if($i == 0) {
                    $whereFilterQuery .= $k . ' ' . $comparator . " " . $number;
                } else {
                    $whereFilterQuery .= ' and '. $k . ' ' . $comparator . " " . $number;
                }
            } else {
                $fk = $db->changeColumnName($k);
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
        $whereFilterQueryWc = "1=1";
    }

    if ($filterValue != null) {
        if($whereFilterQuery != '') {
            $whereFilter = $whereFilterQuery;
        } else {
            $whereFilter = "1=1";
        }
        if($whereFilterQueryWc != '') {
            $whereFilterWc = $whereFilterQueryWc;
        } else {
            $whereFilterWc = "1=1";
        }
    } else {
        $whereFilter = "1=1";
        $whereFilterWc = "1=1";
    }

    $sortName = ($params->sortName) ? $params->sortName: null;
    $sortOrder = ($params->sortOrder) ? $params->sortOrder: null;

    if($sortName != null and $sortOrder != null) {
        $whereSort = "[$sortName] $sortOrder";
    } else {
        $whereSort = '[Start Date] desc';
    }

    $from = $params->from;
    $to = $params->to;
    $whereFromTo = "rn >= $from and rn <= $to";

    $db->query("exec generate_view_ssp $user, '$whereFilter', '$whereFilterWc', '$whereSort', '$whereFromTo'");

    while($db->fetchObject()) {
        if(isset($db->row['Start Date'])) {
            $startDate = $db->row["Start Date"];
            $db->row['Start Date'] =  $startDate->format('Y-m-d H:i:s');
        }
        if (isset($db->row["Planned Time"])) {
            $plannedTime = $db->row["Planned Time"];
            $db->row['Planned Time'] = $plannedTime->format('Y-m-d H:i:s');
        }
        if (isset($db->row["Release Date"])) {
            $releaseDate = $db->row["Release Date"];
            $db->row['Release Date'] = $releaseDate->format('Y-m-d H:i:s');
        }
        if (isset($db->row["Effective Date"])) {
            $effectiveDate = $db->row["Effective Date"];
            $db->row['Effective Date'] = $effectiveDate->format('Y-m-d H:i:s');
        }

        $result[] = array_map('utf8_encode', $db->row);
    }

    if(!isset($result)) {
        $db->query("select column_origin from user_view_settings where user_id = $user order by seq");
        while($db->fetchObject()) {
            $query_res[] = array_map('utf8_encode',$db->row);
        }
        $i = 0;
        $result = array('rn' => '');
        foreach($query_res as $res) {
            foreach($res as $k => $v) {
                if(substr($v, 0,2) == 'D_') {
                    if($i == 0) {
                        $result['Problems Sum'] = null;
                        $result['sh_quan'] = null;
                        $result['sh_unp'] = null;
                        $result['sh_color'] = null;
                        $result['first_assembly'] = null;
                        $i++;
                    }
                    $result[$v] = null;
                } else if ($v == 'Order Status') {
                    $result[$v] = null;
                } else {
                    $result[$v] = 'No data';
                }
            }
        }
        $result = array($result);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);
});

$app->post('/view_by_user_count', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $user = $params->zoneId;

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        $whereFilterQueryWc = '';
        $j = 0;
        foreach($newFilterValue as $k => $v) {
            if($k == 'Start Date' || $k == 'Planned Time' || $k == 'Release Date' || $k == 'Effective Date') {
                $date = $v['value']['date'];
                $comparator = $v['value']['comparator'];
                $fk = $db->changeColumnName($k);
                if($i == 0) {
                    $whereFilterQuery .= $fk . ' ' . $comparator . " ''" . $date . "''";
                } else {
                    $whereFilterQuery .= ' and '. $fk . ' ' . $comparator . " ''" . $date . "''";
                }
                $i++;
            } else if (substr($k, 0, 2) == "D_") {
                if($j == 0) {
                    $whereFilterQueryWc = "[".$k . "] like ''%" . $v['value'] . "%''";
                } else {
                    $whereFilterQueryWc .= " and [" . $k . "] like ''%" . $v['value'] . "%''";
                }
                $j++;
            } else if ($k === 'prod_seq') {
                $number = $v['value']['number'];
                $comparator = $v['value']['comparator'];
                if ($i == 0) {
                    $whereFilterQuery .= $k . ' ' . $comparator . " " . $number;
                } else {
                    $whereFilterQuery .= ' and ' . $k . ' ' . $comparator . " " . $number;
                }
            } else {
                $fk = $db->changeColumnName($k);
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
        $whereFilterQueryWc = "1=1";
    }

    if ($filterValue != null) {
        if($whereFilterQuery != '') {
            $whereFilter = $whereFilterQuery;
        } else {
            $whereFilter = "1=1";
        }
        if($whereFilterQueryWc != '') {
            $whereFilterWc = $whereFilterQueryWc;
        } else {
            $whereFilterWc = "1=1";
        }
    } else {
        $whereFilter = "1=1";
        $whereFilterWc = "1=1";
    }

    $res = $db->queryOne("exec generate_view_ssp_count $user, '$whereFilter', '$whereFilterWc'");

    echo json_encode(['count' => $res]);
});


$app->get('/operations_status', function (Request $request, Response $response, $args) {
    $db = new database();
    $db->query("select status, dsc from dict_operations_status order by sort_order");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});

$app->post('/production_status', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select * from dict_users_view_production_status");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});

$app->post('/update_cell', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $cellId = $params->cellId;
    $cellName = $params->cellName;
    $cellValue = $params->cellValue;
    $table = $params->table; // 1-LNProductionOrder | 2-Deliveries | 3-Problems | 5-Pickinq Queue | 6 - Engraving
    $zoneId = $params->zoneId;
    $res = '';
    if($table == 1) {
        if($cellValue <= 100) {
            $res = $db->queryOne("exec update_max_state_date '$cellId', '$cellName', $cellValue, '0', $zoneId, 0");
        }
    } else if ($table == 2) {
        if ($cellValue != null) {
            if (is_numeric($cellValue)) {
                $db->query("update deliveries set $cellName = $cellValue where wo = '$cellId'");
            } else {
                $db->query("update deliveries set $cellName = '$cellValue' where wo = '$cellId'");
            }
        } else {
            $db->query("update deliveries set $cellName = null where wo = '$cellId'");
        }
    } else if ($table == 6) {
        if($cellValue != null) {
            $db->query("update pre_do_grawerowania set $cellName = '$cellValue' where id = $cellId");
        }
    }

    echo json_encode(["status" => 'updated', 'message' => "$res"]);
});


$app->post('/comments', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $productionOrder = $params->productionOrder;

    $db->query("select id, pdno, replace(convert(varchar,dt,111), '/', '-') as dt, seq, comment, [name] from comments where pdno = '$productionOrder'");

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

$app->post('/add_comment', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $zoneId = $params->zoneId;
    $productionOrder = $params->productionOrder;
    $comment = $params->comment;

    $db->query("exec add_comment $zoneId, '$productionOrder','". $db->convert_ins($comment). "'");

    echo json_encode(["status" => 'Comment Added!']);
});

$app->post('/add_info_1', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $productionOrder = $params->productionOrder;

    $db->query("select id, pdno, replace(convert(varchar,dt,111), '/', '-') as dt, seq, add_info_1, [name] from add_info_1 where pdno = '$productionOrder'");

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

$app->post('/add_add_info_1', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $zoneId = $params->zoneId;
    $productionOrder = $params->productionOrder;
    $comment = $params->comment;

    $db->query("exec add_add_info_1 $zoneId, '$productionOrder','". $db->convert_ins($comment). "'");

    echo json_encode(["status" => 'Add Info 1 Added!']);
});

$app->post('/add_info_2', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $productionOrder = $params->productionOrder;

    $db->query("select id, pdno, replace(convert(varchar,dt,111), '/', '-') as dt, seq, add_info_2, [name] from add_info_2 where pdno = '$productionOrder'");

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

$app->post('/add_add_info_2', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());

    $zoneId = $params->zoneId;
    $productionOrder = $params->productionOrder;
    $comment = $params->comment;

    $db->query("exec add_add_info_2 $zoneId, '$productionOrder','". $db->convert_ins($comment). "'");

    echo json_encode(["status" => 'Add Info 2 Added!']);
});


$app->post('/released_orders', function (Request $request, Response $response, $args) {
    $db = new database();
    $res = $db->queryOne("select count(*) from LNProductionOrder where OrderStatus = 4");
    echo json_encode(['count' => $res]);
});

$app->post('/active_orders', function (Request $request, Response $response, $args) {
    $db = new database();
    $res = $db->queryOne("select count(*) from LNProductionOrder where OrderStatus = 6");
    echo json_encode(['count' => $res]);
});

$app->post('/time_production_status', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $workCenter = $params->workCenter;

    $res = $db->queryOne("select current_state from time_current_state where pdno = '$productionOrder' and cwoc = '$workCenter'");

    if($res == '') {
        $res = 1;
    }

    echo json_encode(['status' => $res]);
});

$app->post('/update_time_production_status', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());
    $cellId = $params->cellId;
    $cellName = $params->cellName;
    $lev1 = $params->lev1;
    $lev2 = $params->lev2;

    $productionStatus = $db->queryOne("select current_state from time_current_state where pdno = '$cellId' and cwoc = '$cellName'");

    //production status: 1-active, 2-paused
    if($productionStatus == 1 or $productionStatus == false) {
        $res = $db->queryOne("exec time_pause '$cellId', '$cellName', '$lev1', '$lev2'");
    } else if ($productionStatus == 2) {
        $res = $db->queryOne("exec time_resume '$cellId', '$cellName'");
    } else {
        $res = 'N/A';
    }

    echo json_encode(['status' => $res]);
});


$app->post('/time_pause_reasons_level_1', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select distinct lev1, lev1dsc from dict_pause_reasons");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});

$app->post('/time_pause_reasons_level_2', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $id1 = $params->level1Id;

    $db->query("select lev2, lev2dsc from dict_pause_reasons where lev1 = '$id1'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});

$app->post('/add_time_pause_reason', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());
    $zoneId = $params->zoneId;
    $id1 = $params->level1Id;
    $id2 = $params->level2Id;

    var_dump($zoneId);
    var_dump($id1);
    var_dump($id2);

});

$app->post('/shortages', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $productionOrder = $params->productionOrder;

    $db->query("exec generate_missing_items '$productionOrder'");

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

$app->post('/check_production_line', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $workCenter = $params->workCenter;

    $db = new database();
    $res = $db->queryOne("select count(*) from dict_prod_lines_by_wc where cwoc = '$workCenter'");
    if($res > 0) {
        echo json_encode(["status" => '1']);
    } else {
        echo json_encode(["status" => '0']);
    }
});

$app->get('/prod_lines_by_wc', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    //workCenter = $params['wc'];

    $db = new database();
    $db->query("select * from dict_prod_lines_by_wc"); //where cwoc = '$workCenter'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(!isset($result)) {
        $res = array('cwoc' => "No data", 'prod_line' => 'No data');
        $result = array($res);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/seq_set_number', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $zoneId = $params->zoneId;
    $position = $params->position;
    if($position === '') {
        $position = '999999';
    }

    $db = new database();
    $res = $db->queryOne("exec seq_set_number '$productionOrder', $zoneId, $position");

    return json_encode($res);
});

$app->post('/set_line', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $prodLine = $params->prodLine;

    $db = new database();

    $res = $db->queryOne("exec set_line '$productionOrder', '$prodLine'");
    return json_encode($res);
});