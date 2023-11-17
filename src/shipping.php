<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app->post('/deliveries', function (Request $request, Response $response, $args) {


    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($i == 0) {
                $whereFilterQuery .=  $k . " like '%" . $v['value'] ."%'";
            } else if ($i < $nfv_count - 1) {
                $whereFilterQuery .= " and ". $k . " like '%" . $v['value'] ."%'";
            } else if ($i == $nfv_count - 1){
                $whereFilterQuery .= " and " . $k . " like '%" . $v['value'] . "%'";
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $searchValue = ($params->searchValue !== null) ? $params->searchValue : null;

    $searchValueQuery = "(wo like '%$searchValue%' or
                        csor like '%$searchValue%' or
                        consignee like '%$searchValue%' or
                        name like '%$searchValue%' or
                        address like '%$searchValue%' or
                        zipcode like '%$searchValue%' or
                        city like '%$searchValue%' or
                        country_name like '%$searchValue%' or
                        delivery_method like '%$searchValue%' or
                        delivery_terms like '%$searchValue%' or
                        created_date like '%$searchValue%' or
                        price_factory like '%$searchValue%' or
                        currency like '%$searchValue%' or
                        status like '%$searchValue%' or
                        weight like '%$searchValue%' or
                        Carrier like '%$searchValue%' or
                        cmr like '%$searchValue%' or
                        Price_PLN like '%$searchValue%' or
                        Price_EUR like '%$searchValue%' or
                        Premium_cost_EUR like '%$searchValue%' or
                        Cargotec_invoice like '%$searchValue%' or
                        Serial_No like '%$searchValue%' or
                        Documents like '%$searchValue%' or
                        Follow_number like '%$searchValue%' or
                        Price_on_invoice_EUR like '%$searchValue%' or
                        Invoice like '%$searchValue%' or
                        Comment like '%$searchValue%')";


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
        $whereSort = 'wo';
    }

    $from = $params->from;
    $to = $params->to;
    $whereFromTo = "rn >= $from and rn <= $to";

    $db->query("with create_view as (
                        select
                          row_number()
                          over (
                            order by $whereSort ) as rn,
                          wo,
                          csor,
                          consignee,
                          [name],
                          [address],
                          zipcode,
                          city,
                          country_name,
                          delivery_method,
                          delivery_terms,
                          left(convert(varchar(10), created_date, 120), 10) as created_date,
                          price_factory,
                          currency,
                          [status],
                          weight,
                          Carrier,
                          cmr,
                          Price_PLN,
                          Price_EUR,
                          Premium_cost_EUR,
                          Cargotec_invoice,
                          Serial_No,
                          Documents,
                          Follow_number,
                          Price_on_invoice_EUR,
                          Invoice,
                          Comment
                        from deliveries where $whereFilter
                    ) select * from create_view where $whereFromTo");

    while($db->fetchObject()) {
        $result[] = array_map('utf8_encode',$db->row);
    }

    if(!isset($result)) {
        $result = array(array('rn' => " ",
            'wo' => 'No data',
            'csor' => 'No data',
            'consignee' => 'No data',
            'name' => 'No data',
            'address' => 'No data',
            'zipcode' => 'No data',
            'city' => 'No data',
            'country_name' => 'No data',
            'delivery_method' => 'No data',
            'created_date' => 'No data',
            'price_factory' => 'No data',
            'currency' => 'No data',
            'status' => 'No data',
            'weight' => 'No data',
            'Carrier' => 'No data',
            'cmr' => 'No data',
            'Delivery_terms' => 'No data',
            'Price_PLN' => 'No data',
            'Price_EUR' => 'No data',
            'Premium_cost_EUR' => 'No data',
            'Cargotec_invoice' => 'No data',
            'Serial_No' => 'No data',
            'Documents' => 'No data',
            'Follow_number' => 'No data',
            'Price_on_invoice_EUR' => 'No data',
            'Invoice' => 'No data',
            'Comment' => 'No data'
        ));
    }

    $resultXML = json_encode($result);
    $response->getBody();
    $response->write($resultXML);


    return $response;

});

$app->post('/deliveries_count', function (Request $request, Response $response, $args) {

    $db = new database();

    $params = json_decode($request->getBody());

    $filterValue = ($params->filterValue !== null ) ? $params->filterValue : null;
    $newFilterValue = json_decode($filterValue, true);

    if(!empty($newFilterValue)) {
        $nfv_count = count($newFilterValue);
        $whereFilterQuery = '';
        $i = 0;
        foreach($newFilterValue as $k => $v) {
            if($i == 0) {
                $whereFilterQuery .=  $k . " like '%" . $v['value'] ."%'";
            } else if ($i < $nfv_count - 1) {
                $whereFilterQuery .= " and ". $k . " like '%" . $v['value'] ."%'";
            } else if ($i == $nfv_count - 1){
                $whereFilterQuery .= " and " . $k . " like '%" . $v['value'] . "%'";
            }
            $i++;
        }
    } else {
        $whereFilterQuery = "1=1";
    }

    $searchValue = ($params->searchValue !== null) ? $params->searchValue : null;

    $searchValueQuery = "(wo like '%$searchValue%' or
                        csor like '%$searchValue%' or
                        consignee like '%$searchValue%' or
                        name like '%$searchValue%' or
                        address like '%$searchValue%' or
                        zipcode like '%$searchValue%' or
                        city like '%$searchValue%' or
                        country_name like '%$searchValue%' or
                        delivery_method like '%$searchValue%' or
                        delivery_terms like '%$searchValue%' or
                        created_date like '%$searchValue%' or
                        price_factory like '%$searchValue%' or
                        currency like '%$searchValue%' or
                        status like '%$searchValue%' or
                        weight like '%$searchValue%' or
                        Carrier like '%$searchValue%' or
                        cmr like '%$searchValue%' or
                        Price_PLN like '%$searchValue%' or
                        Price_EUR like '%$searchValue%' or
                        Premium_cost_EUR like '%$searchValue%' or
                        Cargotec_invoice like '%$searchValue%' or
                        Serial_No like '%$searchValue%' or
                        Documents like '%$searchValue%' or
                        Follow_number like '%$searchValue%' or
                        Price_on_invoice_EUR like '%$searchValue%' or
                        Invoice like '%$searchValue%' or
                        Comment like '%$searchValue%')";


    if($searchValue != null  and $filterValue != null) {
        $whereFilter = $searchValueQuery . ' and ' . $whereFilterQuery;
    } else if ($searchValue != null) {
        $whereFilter = $searchValueQuery;
    } else if ($filterValue != null) {
        $whereFilter = $whereFilterQuery;
    } else {
        $whereFilter = "1=1";
    }


    $res = $db->queryOne("with create_view as (
                        select
                          row_number()
                          over (
                            order by wo ) as rn,
                          wo,
                          csor,
                          consignee,
                          [name],
                          [address],
                          zipcode,
                          city,
                          country_name,
                          delivery_method,
                          delivery_terms,
                          left(convert(varchar(10), created_date, 120), 10) as created_date,
                          price_factory,
                          currency,
                          [status],
                          weight,
                          Carrier,
                          cmr,
                          Price_PLN,
                          Price_EUR,
                          Premium_cost_EUR,
                          Cargotec_invoice,
                          Serial_No,
                          Documents,
                          Follow_number,
                          Price_on_invoice_EUR,
                          Invoice,
                          Comment
                        from deliveries where $whereFilter
                    ) select count(*) from create_view");

    echo json_encode(['count' => $res]);
});
