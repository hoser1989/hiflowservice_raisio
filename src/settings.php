<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//-------------------------------------------------MAIN TAB-------------------------------------------------------------

$app->get('/user_columns', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];

    $db = new database();
    $db->query("select
                      uvc.user_id,
                      uvc.seq,
                      uvc.column_id,
                      duvc.column_display,
                      duvc.visible,
                      duvc.mandatory
                    FROM
                      users_view_columns uvc
                    INNER JOIN
                      dict_users_view_columns duvc on duvc.id = uvc.column_id
                    WHERE
                      uvc.user_id = $userId and duvc.visible = 1
                    ORDER BY
                      uvc.seq");

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

$app->get('/columns_to_add', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];
    $db = new database();
    $db->query("select
                      duvc.id,
                      duvc.column_display
                    from
                      dict_users_view_columns duvc
                    where
                      duvc.id not in (select uvc.column_id from users_view_columns uvc where uvc.user_id = $userId)
                      and duvc.visible = 1");

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

$app->post('/add_column', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $columnId = $params->column_id;
    $userId = $params->user_id;

    $db = new database();

    $db->query("exec add_columns_to_user_view $userId, $columnId");

    echo json_encode(["status" => 'Column Added!']);
});

$app->post('/change_columns_order', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $columns = $params->columns;
    $db = new database();

    foreach($columns as $key=>$value) {
        if($key == 0) {
            $db->query("update users_view_columns set seq = 1 where column_id =". $value->column_id ." and user_id = ". $value->user_id);
        } else {
            $db->query("update users_view_columns set seq = $key + 1 where column_id =". $value->column_id ." and user_id =". $value->user_id);
        }
    }

    echo json_encode(["status" => 'Seq Changed!']);
});

$app->post('/delete_column', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $columnId = $params->column_id;
    $userId = $params->user_id;
    $db = new database();

    $db->query("exec remove_columns_from_users_view_columns $userId, $columnId ");

    echo json_encode(["status" => 'Deleted']);
});

$app->get('/user_wc', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];

    $db = new database();
    $db->query("select
                      uvw.user_id,
                      uvw.seq,
                      uvw.wc_id,
                      duvw.wc_display
                    FROM
                      users_view_wc uvw
                    INNER JOIN
                      dict_users_view_wc duvw ON duvw.id = uvw.wc_id
                    WHERE
                      uvw.user_id = $userId
                    ORDER BY
                      uvw.seq");

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

$app->get('/wc_to_add', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];
    $db = new database();
    $db->query("select
                      duvw.id,
                      duvw.wc_display
                    from
                      dict_users_view_wc duvw
                    where
                      duvw.id not in (select uvw.wc_id from users_view_wc uvw where uvw.user_id = $userId) order by duvw.wc_display");

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

$app->post('/add_wc', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $wcId = $params->wc_id;
    $userId = $params->user_id;

    $db = new database();
    $db->query("exec add_wc_to_user_view_wc $userId, $wcId");
    echo json_encode(["status" => 'WC Added!']);
});

$app->post('/change_wcs_order', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $wcs = $params->wcs;
    $db = new database();

    foreach($wcs as $key=>$value) {
        if($key == 0) {
            $db->query("update users_view_wc set seq = 100 where wc_id =". $value->wc_id . "and user_id = " . $value->user_id);
        } else {
            $db->query("update users_view_wc set seq = $key + 100 where wc_id =". $value->wc_id ."and user_id =". $value->user_id);
        }
    }

    echo json_encode(["status" => 'WC Seq Changed!']);
});

$app->post('/delete_wc', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $wcId = $params->wc_id;
    $userId = $params->user_id;
    $db = new database();

    $db->query("delete from users_view_wc where wc_id = $wcId and user_id = $userId");

    echo json_encode(["status" => 'WC Deleted']);
});

$app->get('/production_statuses_to_add', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];

    $db = new database();
    $db->query("select
                      duvps.id,
                      duvps.production_status_display
                    from
                      dict_users_view_production_status duvps
                    where
                      duvps.id not in (select uvps.production_status_id from users_view_production_status uvps where uvps.user_id = $userId)");

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

$app->get('/production_statuses', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];

    $db = new database();
    $db->query("select
                      uvps.user_id,
                      uvps.production_status_id,
                      duvps.production_status_display
                    FROM
                      users_view_production_status uvps
                    INNER JOIN
                      dict_users_view_production_status duvps ON duvps.id = uvps.production_status_id
                    WHERE
                      uvps.user_id = $userId
                    ORDER BY
                      uvps.production_status_id");

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


$app->post('/add_production_status', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionStatusId = $params->production_status_id;
    $userId = $params->user_id;

    $db = new database();
//    $db->query("insert into users_view_production_status  values ($userId, $productionStatusId)");
    $db->query("exec add_order_status_to_user_view  $userId, $productionStatusId");
    echo json_encode(["status" => 'Production Status Added!']);
});

$app->post('/delete_production_status', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionStatusId = $params->production_status_id;
    $userId = $params->user_id;
    $db = new database();

    $db->query("delete from users_view_production_status where production_status_id = $productionStatusId and user_id = $userId");

    echo json_encode(["status" => 'Production Status Deleted']);
});

$app->get('/production_lines_to_add', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];

    $db = new database();
    $db->query("select
                      duvpl.id,
                      duvpl.production_line_display
                    from
                      dict_user_view_production_lines duvpl
                    where
                      duvpl.id not in (select uvpl.production_line_id from users_view_production_lines uvpl where uvpl.user_id = $userId)");

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

$app->get('/production_lines', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];

    $db = new database();
    $db->query("select
                      uvpl.user_id,
                      uvpl.production_line_id,
                      duvpl.production_line_query,
                      duvpl.production_line_display
                    from
                      users_view_production_lines uvpl
                    left join
                      dict_user_view_production_lines duvpl on duvpl.id = uvpl.production_line_id
                    where uvpl.user_id = $userId");

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

$app->post('/add_production_line', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionLineId = $params->production_line_id;
    $userId = $params->user_id;

    $db = new database();
    //$db->query("insert into users_view_production_lines  values ($userId, $productionLineId)");
    $db->query("exec add_line_to_user_view_line $userId , $productionLineId");
    echo json_encode(["status" => 'Production Line Added!']);
});

$app->post('/delete_production_line', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $productionLineId = $params->production_line_id;
    $userId = $params->user_id;
    $db = new database();

    $db->query("delete from users_view_production_lines where production_line_id = $productionLineId and user_id = $userId");

    echo json_encode(["status" => 'Production Line Deleted']);
});

//--------------------------------------------------PROBLEMS TAB--------------------------------------------------------

$app->get('/user_problems_columns', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];

    $db = new database();
    $db->query("select
                      uvpc.user_id,
                      uvpc.seq,
                      uvpc.column_id,
                      duvpc.column_display,
                      duvpc.visible,
                      duvpc.mandatory
                    from 
                      users_view_problems_columns uvpc
                    inner join 
                      dict_users_view_problems_columns duvpc on duvpc.id = uvpc.column_id
                    where 
                      uvpc.user_id = $userId and duvpc.visible = 1
                    order by 
                      seq");

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


$app->get('/problems_columns_to_add', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];
    $db = new database();
    $db->query("select
                      duvpc.id,
                      duvpc.column_display
                    from
                      dict_users_view_problems_columns duvpc
                    where
                      duvpc.visible = 1 and
                      duvpc.id not in (select uvpc.column_id from users_view_problems_columns uvpc where uvpc.user_id = $userId)");

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

$app->post('/delete_problems_column', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $columnId = $params->column_id;
    $userId = $params->user_id;
    $db = new database();

    $db->query("remove_columns_from_user_view_problems $userId, $columnId");

    echo json_encode(["status" => 'Deleted']);
});

$app->post('/change_problems_columns_order', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $columns = $params->columns;
    $db = new database();

    foreach($columns as $key=>$value) {
        if($key == 0) {
            $db->query("update users_view_problems_columns set seq = 1 where column_id =". $value->column_id ." and user_id = ". $value->user_id);
        } else {
            $db->query("update users_view_problems_columns set seq = $key + 1 where column_id =". $value->column_id ." and user_id =". $value->user_id);
        }
    }

    echo json_encode(["status" => 'Seq Changed!']);
});

$app->post('/add_problems_column', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $userId = $params->user_id;
    $columnId = $params->column_id;

    $db = new database();
    $db->query("exec add_columns_to_user_view_problems $userId, $columnId");

    echo json_encode(["status" => 'Column Added!']);
});

//---------------------------------------------------------COPY SETTINGS -----------------------------------------------

$app->get('/get_workers', function (Request $request, Response $response, $args) {

    $db = new database($host = 'Plsts1-s0044', 'psq', 'Vincent7', 'hiabworkers');
    $db->query("select 
                      distinct zone_id, 
                      user_name 
                    from workers 
                    where 
                      is_working = 'T'");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(!empty($result)) {
        array_walk_recursive($result, function(&$item, $key){
            if(!mb_detect_encoding($item, 'utf-8', true)){
                $item = utf8_encode($item);
            }
        });
    } else  {
        $result = array();
    }



    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);

    return $response;
});

$app->post('/copy_user_settings', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $userFrom = $params->workerFrom;
    $userTo = $params->workerTo;
    $db = new database();

    $db->query("exec copy_user_view_columns $userFrom, $userTo");

    echo json_encode(["status" => 'Settings Coppied!']);
});


//------------------------------------------WAREHOUSE------------------------------------------------

$app->get('/picking_queue_wc_to_add', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];
    $db = new database();
    $db->query("select
                      duvw.id,
                      duvw.wc_display
                    from
                      dict_users_view_wc duvw
                    where
                      duvw.id not in (select uvw.wc_id from users_view_wc_wh_picking_queue uvw where uvw.user_id = $userId) order by duvw.wc_display");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;
});

$app->get('/picking_queue_wc', function (Request $request, Response $response, $args) {

    $params = $request->getQueryParams();
    $userId = $params['user_id'];

    $db = new database();
    $db->query("select
                      uvwwwp.user_id,
                      uvwwwp.wc_id,
                      duvw.wc_display
                    FROM
                      users_view_wc_wh_picking_queue uvwwwp
                    INNER JOIN
                      dict_users_view_wc duvw ON duvw.id = uvwwwp.wc_id
                    WHERE
                      uvwwwp.user_id = $userId
                    ORDER BY uvwwwp.seq");

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

$app->post('/add_wc_to_picking_queue', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $wcId = $params->wc_id;
    $userId = $params->user_id;

    $db = new database();
    $db->query("exec add_wc_to_picking_queue $userId, $wcId");
    echo json_encode(["status" => 'WC Added To Picking Queue!']);
});

$app->post('/remove_wc_from_picking_queue', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $wcId = $params->wc_id;
    $userId = $params->user_id;

    $db = new database();
    $db->query("exec remove_wc_from_picking_queue $wcId, $userId");
    echo json_encode(["status" => 'WC removed from Picking Queue!']);
});

$app->post('/setup_top', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $top = $params->top;
    $userId = $params->user_id;

    $db = new database();
    $db->query("exec wh_setup_top $userId, $top");
    echo json_encode(["status" => 'Top established!']);
});

$app->post('/get_top', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $userId = $params->userId;

    $db = new database();
    $res = $db->queryOne("select [top] from wh_users_top where user_id = $userId");
    echo json_encode(['top' => $res]);
});