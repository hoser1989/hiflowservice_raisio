<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/work_centers', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select distinct id, wc_query, wc_display from dict_users_view_wc");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/work_centers_main', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select id_wc_main, wc_main_dsc from dict_wc_main order by id_wc_main");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/control_place_main', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select * from dict_control_place_main");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/level1', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select distinct id_lev_1, dsc_lev_1 from dict_reasons");

    while($db->fetchObject()) {
        $result[] = array_map('utf8_encode',$db->row);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/level2', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $id1 = isset($params->level1Id) ? $params->level1Id : null;

    if($id1 != null ) {
        $db->query("select distinct id_lev_2, dsc_lev_2 from dict_reasons where id_lev_1 = $id1");

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

$app->post('/level3', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $id1 = isset($params->level1Id) ? $params->level1Id : null;
    $id2 = isset($params->level2Id) ? $params->level2Id: null;

    if($id1 != null && $id2 != null) {
        $db->query("select distinct id_lev_3, dsc_lev_3 from dict_reasons where id_lev_1 = $id1 and id_lev_2 = $id2");

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

$app->post('/level4', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $id1 = isset($params->level1Id) ? $params->level1Id : null;
    $id2 = isset($params->level2Id) ? $params->level2Id : null;
    $id3 = isset($params->level3Id) ? $params->level3Id : null;

    if($id1 != null && $id2 != null && $id3 != null) {
        $db->query("select distinct id_lev_4, dsc_lev_4 from dict_reasons  where id_lev_1 = $id1 and id_lev_2 = $id2 and id_lev_3 = $id3");

        while ($db->fetchObject()) {
            $result[] = array_map('utf8_encode', $db->row);
        }
    }

    if(empty($result)) {
        $result = array('id_lev_4' => '0', 'dsc_lev_4' => null);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});

$app->post('/departments', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $db->query("select * from dict_departments");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/priorities', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $db->query("select * from dict_priorities");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/dream_teams', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $db->query("select * from dict_dream_teames");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});


$app->post('/problems', function (Request $request, Response $response, $args) {

    $db = new Database();
    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            $fk = $db->changeProblemsColumnName($k);
            if($i == 0) {
                $whereFilterQuery .=   $fk . " like ''%" . $v['value'] ."%''";
            } else if ($i < $nfv_count - 1) {
                $whereFilterQuery .= " and ". $fk . " like ''%" . $v['value'] ."%''";
            } else if ($i == $nfv_count - 1){
                $whereFilterQuery .= " and " . $fk . " like ''%" . $v['value'] . "%''";
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $searchValue = ($params->searchValue !== null) ? $params->searchValue : null;

    $searchValueQuery = "(p.id like ''%$searchValue%'' or
                        p.production_order like ''%$searchValue%'' or
                        p.wc like ''%$searchValue%'' or
                        p.id_department like ''%$searchValue%'' or
                        dd.[desc] like ''%$searchValue%'' or
                        p.id_cause like ''%$searchValue%'' or
                        p.seq like ''%$searchValue%'' or
                        dr.dsc_lev_1 like ''%$searchValue%'' or
                        dr.dsc_lev_2 like ''%$searchValue%'' or
                        dr.dsc_lev_3 like ''%$searchValue%'' or
                        dr.dsc_lev_4 like ''%$searchValue%'' or
                        p.cause_status like ''%$searchValue%'' or
                        p.comment like ''%$searchValue%'' or
                        p.item like ''%$searchValue%'' or
                        p.item_dsc like ''%$searchValue%'' or
                        p.planner like ''%$searchValue%'' or
                        p.approved like ''%$searchValue%'' or
                        p.[login] like ''%$searchValue%'')";


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
        if($sortName == 'dsc_lev_1' || $sortName == 'dsc_lev_2' || $sortName == 'dsc_lev_3' || $sortName == 'dsc_lev_4') {
            $whereSort = "dr.$sortName $sortOrder";
        } else if ($sortName == 'desc') {
            $whereSort = "dd.$sortName $sortOrder";
        } else {
            $whereSort = "p.$sortName $sortOrder";
        }
    } else {
        $whereSort = 'p.blocked desc, p.priority desc, p.id desc';
    }

    $from = $params->from;
    $to = $params->to;
    $whereFromTo = "rn >= $from and rn <= $to";

    $user = $params->zoneId;


    $db->query("exec generate_problems_view $user, '$whereFilter', '$whereSort', '$whereFromTo'");

    while($db->fetchObject()) {
        if(isset($db->row['reported_time'])) {
            $reportedTime = $db->row["reported_time"];
            $db->row['reported_time'] =  $reportedTime->format('Y-m-d H:i:s');
        }
        $result[] = array_map('utf8_encode', $db->row);
    }

    if(!isset($result)) {
        $db->query("select 
                          duvpc.column_origin 
                        from users_view_problems_columns uvwc
                        inner join dict_users_view_problems_columns duvpc on duvpc.id = uvwc.column_id
                        where uvwc.user_id = $user
                        order by uvwc.seq");
        while($db->fetchObject()) {
            $query_res[] = array_map('utf8_encode',$db->row);
        }
        $i = 0;
        $result = array('rn' => "No data", 'id_level_1' => 'No data', 'id_level_2' => 'No data', 'id_level_3' => 'No data', 'id_level_4' => 'No data');
        foreach($query_res as $res) {
            foreach($res as $k => $v) {
                $result[$v] = 'No data';
            }
        }
        $result = array($result);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/problems_count', function (Request $request, Response $response, $args) {

    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            $fk = $db->changeProblemsColumnName($k);
            if($i == 0) {
                $whereFilterQuery .=   $fk . " like ''%" . $v['value'] ."%''";
            } else if ($i < $nfv_count - 1) {
                $whereFilterQuery .= " and ". $fk . " like ''%" . $v['value'] ."%''";
            } else if ($i == $nfv_count - 1){
                $whereFilterQuery .= " and " . $fk . " like ''%" . $v['value'] . "%''";
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $searchValue = ($params->searchValue !== null) ? $params->searchValue : null;

    $searchValueQuery = "(p.id like ''%$searchValue%'' or
                        p.production_order like ''%$searchValue%'' or
                        p.wc like ''%$searchValue%'' or
                        p.id_department like ''%$searchValue%'' or
                        dd.[desc] like ''%$searchValue%'' or
                        p.id_cause like ''%$searchValue%'' or
                        p.seq like ''%$searchValue%'' or
                        dr.dsc_lev_1 like ''%$searchValue%'' or
                        dr.dsc_lev_2 like ''%$searchValue%'' or
                        dr.dsc_lev_3 like ''%$searchValue%'' or
                        dr.dsc_lev_4 like ''%$searchValue%'' or
                        p.cause_status like ''%$searchValue%'' or
                        p.comment like ''%$searchValue%'' or
                        p.item like ''%$searchValue%'' or
                        p.item_dsc like ''%$searchValue%'' or
                        p.planner like ''%$searchValue%'' or
                        p.approved like ''%$searchValue%'' or
                        p.[login] like ''%$searchValue%'')";


    if($searchValue != null  and $filterValue != null) {
        $whereFilter = $searchValueQuery . ' and ' . $whereFilterQuery;
    } else if ($searchValue != null) {
        $whereFilter = $searchValueQuery;
    } else if ($filterValue != null) {
        $whereFilter = $whereFilterQuery;
    } else {
        $whereFilter = "1=1";
    }

    $user = $params->zoneId;

    $res = $db->queryOne("exec generate_problems_view_count $user, '$whereFilter'");

    echo json_encode(['count' => $res]);
});


$app->post('/add_problem', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    if(isset($params->productionOrder) != false) {
        $productionOrder = $params->productionOrder;
    } else {
        $productionOrder = '';
    }
    if(isset($params->wc) != false) {
        $wc = $params->wc;
    } else {
        $wc = '';
    }
    if(isset($params->department) != false) {
        $department = $params->department;
    } else {
        $department = 0;
    }
    if(isset($params->assDepartment) != false) {
        $assDepartment = $params->assDepartment;
    } else {
        $assDepartment = 0;
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
    if(isset($params->level4) != false) {
        $level4 = is_null($params->level4) ? 0 : $params->level4;
    } else {
        $level4 = 0;
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
    if(isset($params->item) != false) {
        $item = (is_null($params->item) || $params->item == '') ?  '0' : $params->item;
    } else {
        $item = '0';
    }
    if(isset($params->quantity) != false) {
        $quantity = (is_null($params->quantity) || $params->quantity == '') ?  '0' : $params->quantity;
    } else {
        $quantity = '0';
    }
    if(isset($params->priority) != false) {
        $priority = (is_null($params->priority) || $params->priority == '') ?  '0' : $params->priority;
    } else {
        $priority = '0';
    }
    if(isset($params->blocked) != false) {
        $blocked = (is_null($params->blocked) || $params->blocked == '') ?  '0' : 1;
    } else {
        $blocked = '0';
    }
    if(isset($params->user) != false) {
        $user = is_null($params->user)   ? '0' : $params->user;
    } else {
        $user = 0;
    }

    if($productionOrder != '' and $wc != '' and $department != 0 and $assDepartment != 0 and $level1 != 0 and $level2 != 0) {
        $res = $db->queryOne("exec add_to_problems '$productionOrder', '$wc', $level1,$level2,$level3,$level4,$department, $assDepartment, '" . $db->convert_ins($comment) . "','$item', $user, $quantity, $priority, $blocked");

        echo json_encode(["status" => "$res"]);
    } else {
        echo json_encode(['status' => 'Sprawdz']);
    }
});

$app->post('/reply_problems', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $id = $params->id;
    $department = $params->department;
    $comment_to_check = is_null($params->comment) ? '' : $params->comment;
    if(preg_match( "/'/",$comment_to_check)) {
        $comment = str_replace("'", "''",$comment_to_check);
    } else {
        $comment = $comment_to_check;
    }

    $user = $params->user;
    $assDepartment = $params->assDepartment;

    foreach($id as  $k) {
        $row_id = (int)$k->id;
        $id_cause = (int)$k->id_cause;
        $id_cause_2 = $k->id_cause_2;
        $production_order = $k->production_order;

        $res = $db->queryOne("exec reply_to_problems '". $db->convert_ins($comment) ."','$user',$row_id,$department,$assDepartment,$id_cause,'$production_order'");
    }

    echo json_encode(["status" => $res]);

});

$app->post('/level1_causes', function (Request $request, Response $response, $args) {

    $db = new database();
    $db->query("select distinct id_lev_1, dsc_lev_1  from dict_causes");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/level2_causes', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $id1 = $params->level1Id;

    $db->query("select distinct id_lev_2, dsc_lev_2 from dict_causes where id_lev_1 = $id1");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }


    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;

});

$app->post('/place_of_error_main', function (Request $request, Response $response, $args) {

    $db = new database($host = 'Plsts1-s0044', $user = 'psq', $password = 'Vincent7', $db = 'qa_out_multi');
    $db->query("select id as id_lev_1, miejsce as dsc_lev_1 from m_l_miejsca_wystapien_bledow_glowne where id_firmy = 1 and czy_widoczny = 1 order by seq");

    while($db->fetchObject()) {
        $result[] = array_map('utf8_encode',$db->row);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/place_of_error', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $id = $params->id;

    $db = new database($host = 'Plsts1-s0044', $user = 'psq', $password = 'Vincent7', $db = 'qa_out_multi');
    $db->query("select id as id_lev_2, miejsce as dsc_lev_2 from m_l_miejsca_wystapien_bledow where id_miejsca_wystapienia_bledu_glowne = $id and czy_widoczny = 1 order by seq");

    while($db->fetchObject()) {
        $result[] = array_map('utf8_encode',$db->row);
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/end_problems', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $id = $params->id;
    $department = $params->department;
    $levelId1 = $params->level1Id;
    $levelId2 = $params->level2Id;
    $comment_to_check = $params->end_comment;
    $user = $params->user;
    if(preg_match( "/'/",$comment_to_check)) {
        $comment = str_replace("'", "''",$comment_to_check);
    } else {
        $comment = $comment_to_check;
    }
    $placeOfErrorId1 = $params->placeOfError1;
    $placeOfErrorId2 = $params->placeOfError2;

    foreach($id as  $k) {
        $id = $k->id;
        $production_order = $k->production_order;
        $id_cause = $k->id_cause;
        $id_cause_2 = $k->id_cause_2;

        $res = $db->queryOne("exec end_problems $id, '$production_order', $id_cause, $id_cause_2, $department, $levelId1, $levelId2,'" . $db->convert_ins($comment) . "', '$user', $placeOfErrorId1, $placeOfErrorId2");

    }
    echo json_encode(["status" => $res]);

});

$app->post('/edit_problem', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $wc = $params->wc;
    $idCause = $params->idCause;
    $department = $params->department;
    $departmentAss = $params->departmentAss;
    $level1 = is_null($params->level1) ? 0 : $params->level1;
    $level2 = is_null($params->level2) ? 0 : $params->level2;
    $level3 = is_null($params->level3) ? 0 : $params->level3;
    $level4 = is_null($params->level4) ? 0 : $params->level4;
    if(isset($params->item) != false) {
        $item = (is_null($params->item) || $params->item == '') ?  '0' : $params->item;
    } else {
        $item = '0';
    }
    if(isset($params->quantity) != false) {
        $quantity = (is_null($params->quantity) || $params->quantity == '') ?  '0' : $params->quantity;
    } else {
        $quantity = '0';
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
    $user = $params->user;
    $productionOrderHook = $params->productionOrderHook;
    $wcHook = $params->wcHook;
    $idCauseHook = $params->idCauseHook;
    $idCause2Hook = $params->idCause2Hook;
    if(isset($params->priority) != false) {
        $priority = (is_null($params->priority) || $params->priority == '') ?  '0' : $params->priority;
    } else {
        $priority = '0';
    }
    if(isset($params->blocked) != false) {
        $blocked = (is_null($params->blocked) || $params->blocked == '') ?  '0' : 1;
    } else {
        $blocked = '0';
    }

    $res = $db->queryOne("exec edit_problem  $department, 
                                        $departmentAss, 
                                        $level1,
                                        $level2,
                                        $level3,
                                        $level4,
                                        '$item',
                                        $quantity, 
                                        '$comment', 
                                        '$user',
                                        '$productionOrderHook',
                                        $idCauseHook,
                                        $idCause2Hook,
                                        $priority,
                                        $blocked");

    echo json_encode(["status" => $res]);

});

$app->post('/add_related_problem', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());

    $productionOrder = $params->productionOrder;
    $wc = $params->wc;
    $id_cause = $params->id_cause;
    $department = $params->department;
    $assDepartment = $params->assDepartment;
    $level1 = is_null($params->level1) ? 0 : $params->level1;
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
    if(isset($params->level4) != false) {
        $level4 = is_null($params->level4) ? 0 : $params->level4;
    } else {
        $level4 = 0;
    }
    if(isset($params->comment) != false) {
        $comment = is_null($params->comment) ? '' : $params->comment;
    } else {
        $comment = '';
    }
    if(isset($params->item) != false) {
        $item = is_null($params->item) ? '' : $params->item;
    } else {
        $item = '';
    }
    if(isset($params->user) != false) {
        $user = is_null($params->user) ? '' : $params->user;
    } else {
        $user = '';
    }

    $id_cause_2 = $db->queryOne("select max(id_cause_2)+1 from problems where production_order = '$productionOrder' and wc = 'D_' + '$wc' and id_cause = $id_cause");

    $db->query("exec add_related_problem '$productionOrder', 'D_$wc', $id_cause, $id_cause_2, $level1,$level2,$level3,$level4,$department, $assDepartment, '". $db->convert_ins($comment) ."','$item', '". $db->convert_ins($user) ."'");
    echo json_encode(["status" => 'inserted']);

});

$app->post('/update_approved', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $idCause = $params->idCause;
    $idCause2 = $params->idCause2;
    $user = $params->user;
    $approved = $params->approved;
//    $db->query("update problems set approved = $approved, approved_by = '$user' where production_order = '$productionOrder' and id_cause = $idCause and id_cause_2 = $idCause2");
    $db->query("exec approve_problem  '$productionOrder', $idCause , $idCause2, '$user', $approved");

    echo json_encode(["status" => 'updated']);

});

$app->post('/update_department', function (Request $request, Response $response, $args) {

    $db = new database();
    $params = json_decode($request->getBody());
    $productionOrder = $params->productionOrder;
    $idCause = $params->idCause;
    $idCause2 = $params->idCause2;
    $department = $params->department;

    $db->query("update problems set id_department = $department where production_order = '$productionOrder' and id_cause = $idCause and id_cause_2 = $idCause2");

    echo json_encode(["status" => 'updated']);

});


$app->post('/problem_details', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());
//   $productionOrder = $params->productionOrder;
//   $idCause = $params->idCause;
    $id = $params->id;

    foreach($id as  $k) {
        $production_order = $k->production_order;
        $id_cause = $k->id_cause;
        $id_cause_2 = $k->id_cause_2;
        $db->query("select
                            p.id,
                            p.reported_time,
                            p.production_order,
                            replace(p.wc, 'D_', '') as wc,
                            p.id_department,
                            dd.[desc],
                            p.id_department_ass,
                            dd2.[desc] as desc_ass,
                            p.id_cause,
                            p.id_cause_2,
                            p.seq,
                            p.id_level_1,
                            dr.dsc_lev_1,
                            p.id_level_2,
                            dr.dsc_lev_2,
                            p.id_level_3,
                            dr.dsc_lev_3,
                            p.id_level_4,
                            dr.dsc_lev_4,
                            p.cause_status,
                            p.comment,
                            p.item,
                            p.item_dsc,
                            p.planner,
                            p.approved,
                            p.[login],
                            pc.id_level_1 as cause_level_1,
                            dc.dsc_lev_1 as dsc_cause_level_1,
                            pc.id_level_2 as cause_level_2,
                            dc.dsc_lev_2 as dsc_cause_level_2,
                            pc.end_department,
                            dd_end.[desc] as end_department_desc,
                            pc.end_comment
                          from
                           problems p
                          left join dict_reasons dr on
                          (dr.id_lev_1 = p.id_level_1
                          and dr.id_lev_2 = p.id_level_2
                          and dr.id_lev_3 = p.id_level_3
                          and dr.id_lev_4 = p.id_level_4
                          )
                          left join
                            dict_departments dd on dd.id = p.id_department
                          left join
                            dict_departments dd2 on dd2.id = p.id_department_ass
                          left join
                          (select
                            production_order,
                            id_cause, 
                            id_cause_2,
                            max(seq) as maxseq
                          from problems_causes
                          group by production_order, id_cause, id_cause_2) m_pc on (m_pc.production_order = p.production_order and m_pc.id_cause = p.id_cause and m_pc.id_cause_2 = p.id_cause_2)
                          left join problems_causes pc on (pc.production_order = m_pc.production_order and pc.id_cause = m_pc.id_cause and pc.seq = m_pc.maxseq)
                          left join dict_causes dc on pc.id_level_1 = dc.id_lev_1 and pc.id_level_2 = dc.id_lev_2
                          left join dict_departments dd_end on dd_end.id = pc.end_department
                        where p.production_order = '$production_order' and p.id_cause = $id_cause and p.id_cause_2 = $id_cause_2
                        ");

        while($db->fetchObject()) {
            if(isset($db->row['reported_time'])) {
                $startDate = $db->row["reported_time"];
                $db->row['reported_time'] =  $startDate->format('Y-m-d H:i:s');
            }
            $result[] = $db->row;
        }
    }
    //--------------------------------------------------------------encode array to UTF-8 ----------------------------------
    array_walk_recursive($result, function(&$item, $key){
        if(!mb_detect_encoding($item, 'utf-8', true)){
            $item = utf8_encode($item);
        }
    });
   //----------------------------------------------------------------------------------------------------------------------

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

});


$app->post('/check_if_approved', function (Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());
    $id = $params->id;
    foreach ($id as $k) {
        $val = $db->queryOne("select
                                      approved
                                    from
                                      problems p
                                    inner join
                                      (select
                                        max(seq) as maxseq,
                                        production_order,
                                        id_cause,
                                        id_cause_2
                                      from problems
                                      where
                                        production_order like '$k->production_order' and id_cause = $k->id_cause and id_cause_2 = $k->id_cause_2
                                      group by
                                        production_order, id_cause, id_cause_2
                                      ) pr
                                      on
                                        pr.production_order = p.production_order and
                                        pr.id_cause = p.id_cause and
                                        pr.id_cause_2 = p.id_cause_2 and
                                        pr.maxseq = p.seq");
        $data[$k->production_order . '| ' .$k->id_cause] = $val;
    }

    return json_encode($data);
});
