<?php

class database {
    private $db_lnk = null;
    private $result = null;
    public $row = array();

//    //local DB
//    function __construct($host = 'localhost\SQL', $user = 'sa', $password = 'Admin1989', $db = 'letsc_dev'){
//        $this->connect($host, $user, $password, $db);
//    }

    //LetsC dev - dev db
    function __construct($host = 'firao1-s0018', $user = 'hiflow', $password = 'hiflow', $db = 'hiflow_qa'){
        $this->connect($host, $user, $password, $db);
    }

    //LetsC prod - prod db
//    function __construct($host = 'plsts1-s0031\app', $user = 'letsc', $password = 'letsc', $db = 'hiflow'){
//        $this->connect($host, $user, $password, $db);
//    }

    function connect($host, $user, $password, $db){
        $this->db_lnk = sqlsrv_connect($host,
            [
                "UID" => $user,
                "PWD" => $password,
                "Database" => $db
            ]);
    }

    function close() {
        if (!sqlsrv_close($this->db_lnk)){
            throw new Exception("Blad podczas zamykania polaczenia z serwerem <br />");
        }
    }

    function query($sql){

        $this->result = sqlsrv_query($this->db_lnk, $sql);

    }

    public function queryOne($sql){
        $this->query($sql);
        $this->fetch();
        return $this->__get(0);
    }

    function fetch($fetchType = SQLSRV_FETCH_BOTH) {
        $this->row = sqlsrv_fetch_array($this->result, $fetchType);
        return $this->row !== null && $this->row !== false;
    }

    function fetchObject() {
        return $this->fetch(SQLSRV_FETCH_ASSOC);
    }

    public function __get($name)
    {
        return isset($this->row) ? $this->row[$name] : false;
    }

    function changeColumnName($k) {
        switch($k) {
            case 'Production Order': $k = 'ProductionOrder';
                break;
            case 'Item No': $k = 'ItemNo';
                break;
            case 'Item Description': $k = '[Description]';
                break;
            case 'Start Date': $k = 'cast(StartDate as date)';
                break;
            case 'Serial Number': $k = 'SerialNumber';
                break;
            case 'ItemNo': $k = 'ItemGroup';
                break;
            case 'Item Group': $k = 'ItemNo';
                break;
            case 'Order Status': $k = 'OrderStatus';
                break;
            case 'RAL Code': $k = 'RALCode';
                break;
            case 'Effectivity Unit': $k = 'EFFN';
                break;
            case 'Customer Order No': $k = 'CustomerOrderNo';
                break;
            case 'CSales Order No': $k = 'CSalesOrderNo';
                break;
            case 'Sales Order LN': $k = 'SalesOrderLN';
                break;
            case 'Planned Time': $k = 'cast(PlannedTime as date)';
                break;
            case 'Release Date': $k = 'cast(ReleaseDate as date)';
                break;
            case 'Effective Date': $k = 'cast(efdt as date)';
                break;
            case 'Type of Order': $k = 'type_ab';
                break;
            case 'Shipment Week': $k = 'ShipmentWeek';
                break;
            case 'Problems Sum': $k  = '[Problems Sum]';
                break;
            case 'prod_line': $k = 'o.prod_line';
                break;
            case 'prod_seq': $k = 'o.prod_seq';
                break;
        }
        return $k;
    }

    function changeProblemsColumnName($k) {
        switch($k) {
            case 'id': $k = 'p.id';
                break;
            case 'cause_status': $k = 'p.cause_status';
                break;
            case 'wc': $k = 'p.wc';
                break;
            case 'production_order': $k = 'p.production_order';
                break;
            case 'dt_id': $k = 'wr.dt_id';
                break;
            case 'id_wc_main': $k = 'dwm.id_wc_main';
                break;
            case 'wc_main_dsc': $k = 'dwm.wc_main_dsc';
                break;
            case 'id_control_place_main': $k = 'dcpm.id_control_place_main';
                break;
            case 'dsca': $k = 'dcpm.dsca';
                break;
            case 'id_control_place_main': $k = 'dcpm.id_control_place_main';
                break;
            case 'id_department': $k = 'p.id_department';
                break;
            case 'id_department_ass': $k = 'p.id_department_ass';
                break;
            case 'id_cause': $k = 'p.id_cause';
                break;
            case 'id_cause_2': $k = 'p.id_cause_2';
                break;
            case 'seq': $k = 'p.seq';
                break;
            case 'id_level_1': $k = 'p.id_level_1';
                break;
            case 'dsc_lev_1': $k = 'dr.dsc_lev_1';
                break;
            case 'id_level_2': $k = 'p.id_level_2';
                break;
            case 'dsc_lev_2': $k = 'dr.dsc_lev_2';
                break;
            case 'id_level_3': $k = 'p.id_level_3';
                break;
            case 'dsc_lev_3': $k = 'dr.dsc_lev_3';
                break;
            case 'id_level_4': $k = 'p.id_level_4';
                break;
            case 'dsc_lev_4': $k = 'dr.dsc_lev_4';
                break;
            case 'comment': $k = 'p.comment';
                break;
            case 'item': $k = 'p.item';
                break;
            case 'item_dsc': $k = 'p.item_dsc';
                break;
            case 'planner': $k = 'p.planner';
                break;
            case 'approved': $k = 'p.approved';
                break;
            case '[login]': $k = 'p.[login]';
                break;
            case 'id_level_1': $k = 'pc.id_level_1';
                break;
            case 'dsc_lev_1': $k = 'dc.dsc_lev_1';
                break;
            case 'id_level_2': $k = 'pc.id_level_2';
                break;
            case 'dsc_lev_2': $k = 'dc.dsc_lev_2';
                break;
            case 'end_department': $k = 'pc.end_department';
                break;
            case 'end_comment': $k = 'pc.end_comment';
                break;
        }
        return $k;
    }

    function changeWarehouseColumnName($k) {
        switch($k) {
            case 'pdno': $k = 'wpq.pdno';
                break;
            case 'StartDate': $k = 'lp.StartDate';
                break;
            case 'cwoc': $k = 'wpq.cwoc';
                break;
            case 'zone': $k = 'wpq.zone';
                break;
            case 'nr_table': $k = 'wpq.nr_table';
                break;
            case 'free_buffer_date': $k = 'wpq.free_buffer_date';
                break;
            case 'free_buffer_login': $k = 'wpq.free_buffer_login';
                break;
            case 'status_during_picking': $k = 'wpq.status_during_picking';
                break;
            case 'date_during_picking': $k = 'wpq.date_during_picking';
                break;
            case 'status_picked': $k = 'wpq.status_picked';
                break;
            case 'date_picked': $k = 'wpq.date_picked';
                break;
            case 'login': $k = 'wpq.[login]';
                break;
            case 'comment': $k = 'c.comment';
                break;
        }
        return $k;
    }

    function changeFastLineColumnName($k) {
        switch($k) {
            case 'pdno': $k = 'd.pdno';
                break;
            case 'cwoc': $k = 'd.cwoc';
                break;
            case 'fb_dt': $k = 'pq.fb_dt';
                break;
            case 'status': $k = 'st.status';
                break;
            case 'user_name': $k = 'st.user_name';
                break;
        }
        return $k;
    }

    function changeKanbanColumnName($k) {
        switch($k) {
            case 'id': $k = 'kr.id';
                break;
            case 'item': $k = 'kr.item';
                break;
            case 'dsca': $k = 'kr.dsca';
                break;
            case 'dt_called': $k = 'kr.dt_called';
                break;
            case 'wc': $k = 'kr.wc';
                break;
            case 'status': $k = 'kr.status';
                break;
            case 'who_called': $k = 'kr.who_called';
                break;
            case 'box_quan': $k = 'kr.box_quan';
                break;
            case 'dt_start_picking': $k = 'kr.dt_start_picking';
                break;
            case 'dt_delivered': $k = 'kr.dt_delivered';
                break;
            case 'who_delivered': $k = 'kr.who_delivered';
                break;
            case 'comment': $k = 'kr.comment';
                break;
        }
        return $k;
    }

    public function convert_ins($str)
    {
        return iconv("utf-8","CP1250",$str);
    }

    public function updateFilter($zoneId, $columnId, $columnValue, $id) {
        $db = new database();
        $check = $db->queryOne("select column_id from users_view_defaults_filters where zone_id = $zoneId and column_id = $columnId and table_id = $id");
        if($check) {
            if($columnValue != '' || $columnValue != null) {
                $db->query("update users_view_defaults_filters set default_filter = '$columnValue' where zone_id = $zoneId and column_id = $columnId and table_id = $id");
            } else {
                $db->query("delete from users_view_defaults_filters where zone_id = $zoneId and column_id = $columnId and table_id = $id");
            }
        } else {
            $db->query("insert into users_view_defaults_filters values ($zoneId, $columnId, '$columnValue', $id)");
        }
    }

    public function updateSort($zoneId, $sortName, $sortOrder, $id) {
        $db = new database();
        $check = $db->queryOne("select sort_name from users_view_defaults_sort where zone_id = $zoneId and table_id = $id");
        if($check) {
            if($sortName != '' || $sortOrder != null) {
                $db->query("update users_view_defaults_sort set sort_name = '$sortName', sort_order = '$sortOrder' where zone_id = $zoneId and table_id = $id");
            } else {
                $db->query("delete from users_view_defaults_sort where zone_id = $zoneId and table_id = $id");
            }
        } else {
            $db->query("insert into users_view_defaults_sort values ($zoneId, '$sortName', '$sortOrder', $id)");
        }
    }

    public function convert($str)
    {
        return iconv("ISO-8859-1","UTF-8//TRANSLIT",$str);
    }

    public function convert_arr_with_pic_to_json($array, $imageSize)
    {
        $db = new database();
        $result8 = [];

        foreach($array as $arr) {
            $result8_tmp = [];
            foreach($arr as $k => $v) {
                if($k == 'name') {
                    $docName = substr($v, strrpos($v, ".") +1);
                    if($docName == 'png') {
                        $docName = 'image/png';
                    } else if ($docName == 'jpeg'){
                        $docName = 'image/jpeg';
                    } else if ($docName == 'pdf') {
                        $docName = 'application/pdf';
                    } else if ($docName == 'xls' || $docName == 'xlsx') {
                        $docName = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    } else if ($docName == 'doc' || $docName == 'docx') {
                        $docName = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    }
                }


                if($k == 'file_stream') {
                    if ($v) {
                        if($docName != 'application/pdf' && $docName != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' && $docName != 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                            $srcImg = imagecreatefromstring($v);
                            $srcImgWidth = imagesx($srcImg);
                            $srcImgHeight = imagesy($srcImg);
                            $dstImgWidth = $imageSize;
                            $dstImgHeight = $dstImgWidth * $srcImgHeight / $srcImgWidth;
                            $dstImg = imagecreatetruecolor($dstImgWidth, $dstImgHeight);
                            imagecopyresized(
                                $dstImg, $srcImg,
                                0, 0, 0, 0,
                                $dstImgWidth, $dstImgHeight,
                                $srcImgWidth, $srcImgHeight
                            );
                            ob_start();
                            imagejpeg($dstImg, null, 100);
                            $contents = ob_get_contents();
                            ob_end_clean();
                            $result8_tmp[$k] = "data:$docName;base64," . base64_encode($contents);
                        } else {
                            $result8_tmp[$k] = "data:$docName;base64," . base64_encode($v);
                        }
                    } else {
                        $result8_tmp[$k] = null;
                    }
                } else {
                    $result8_tmp[$k] =  $db->convert($v);
                }
            }
            $result8[] = $result8_tmp;
        }
        echo $result = json_encode($result8);
    }

    public function convert_arr_with_pic_to_json_original_size($array)
    {
        $db = new database();
        $result8 = [];

        foreach($array as $arr) {
            $result8_tmp = [];
            foreach($arr as $k => $v) {
                if($k == 'name') {
                    $docName = substr($v, strrpos($v, ".") +1);
                    if($docName == 'png') {
                        $docName = 'image/png';
                    } else if ($docName == 'jpeg'){
                        $docName = 'image/jpeg';
                    } else if ($docName == 'pdf') {
                        $docName = 'application/pdf';
                    } else if ($docName == 'xls' || $docName = 'xlsx') {
                        $docName = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    } else if ($docName == 'doc' || $docName == 'docx') {
                        $docName = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    }
                }
                if($k == 'file_stream') {
                    if ($v) {
                        $result8_tmp[$k] = "data:$docName;base64," . base64_encode($v);
                    } else {
                        $result8_tmp[$k] = null;
                    }
                } else {
                    $result8_tmp[$k] =  $db->convert($v);
                }
            }
            $result8[] = $result8_tmp;
        }

        //echo $result = "{ \"$name\":" . json_encode($result8) . "}";
        echo $result = json_encode($result8);
    }

}