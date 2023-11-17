<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post('/filters', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $zoneId = $params->zoneId;
    $tableId = $params->tableId;
    $db = new database();

    $db->query("select zone_id, column_id, default_filter from users_view_defaults_filters where zone_id = $zoneId and table_id = $tableId");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(isset($result)) {
        $resultXML = json_encode($result);
        $response->getBody();
        $response->write($resultXML);
    } else {
        $response = '0';
    }

    return $response;
});

$app->post('/update_filters', function(Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $filters = $params->params;
    $zoneId = $params->zoneId;
    $tableId = $params->tableId;

    $db = new database();

    if($tableId == 3) {
        if($filters->name ='approved') {
            $db->updateFilter($zoneId, 20, $filters->approved, $tableId);
        }
        if ($filters->name = 'status') {
            $db->updateFilter($zoneId, 2, $filters->status, $tableId);
        }
        if ($filters->name = 'wc') {
            $db->updateFilter($zoneId, 5, $filters->wc, $tableId);
        }
        if ($filters->name = 'department') {
            $db->updateFilter($zoneId, 10, $filters->department, $tableId);
        }
        if ($filters->name = 'department_ass') {
            $db->updateFilter($zoneId, 11, $filters->department_ass, $tableId);
        }
        if ($filters->name = 'swat') {
            $db->updateFilter($zoneId, 7, $filters->swat, $tableId);
        }
        if ($filters->name = 'id_wc_main') {
            $db->updateFilter($zoneId, 8, $filters->id_wc_main, $tableId);
        }
        if ($filters->name = 'id_control_place_main') {
            $db->updateFilter($zoneId, 9, $filters->id_control_place_main, $tableId);
        }
        if ($filters->name = 'planner') {
            $db->updateFilter($zoneId, 19, $filters->planner, $tableId);
        }
    }
    echo json_encode('filters updated!');
});

$app->post('/sort', function (Request $request, Response $response, $args) {

    $params = json_decode($request->getBody());
    $zoneId = $params->zoneId;
    $tableId = $params->tableId;

    $db = new database();
    $db->query("select zone_id, sort_name, sort_order from users_view_defaults_sort where zone_id = $zoneId and table_id = $tableId");

    while($db->fetchObject()) {
        $result[] = $db->row;
    }

    if(isset($result)) {
        $resultXML = json_encode($result);
        $response->getBody();
        $response->write($resultXML);
    } else {
        $response = '0';
    }

    return $response;
});

$app->post('/update_sort', function(Request $request, Response $response, $args) {
    $params = json_decode($request->getBody());
    $zoneId = $params->zoneId;
    $sortName = $params->sortName;
    $sortOrder = $params->sortOrder;
    $table_id = $params->tableId;
    $db = new database();

    $db->updateSort($zoneId, $sortName, $sortOrder, $table_id);

    echo json_encode('sort changed!');
});


$app->post('/delete_defaults', function(Request $request, Response $response, $args) {
    $db = new database();
    $params = json_decode($request->getBody());
    $zoneId = $params->zoneId;
    $table_id = $params->tableId;

    $db->query("delete from users_view_defaults_filters where zone_id = $zoneId and table_id = $table_id");
    $db->query("delete from users_view_defaults_sort where zone_id = $zoneId and table_id = $table_id");

    echo json_encode('filters deleted!');
});