<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Slim\App;
use Slim\Container;


ini_set('max_execution_time', 300);
require 'vendor/autoload.php';
require_once 'lib/database.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$container = new Container;
$app = new App($config);
$container = $app->getContainer();

$container['logger'] = function($c) {
    $logger = new Logger('kosja');
    $file_handler = new StreamHandler("C:/dev_php/hiflowService/logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};


require_once 'src/view_by_user.php';
require_once 'src/generate_bom.php';
require_once 'src/authenticate.php';
require_once 'src/settings.php';
require_once 'src/shipping.php';
require_once 'src/problems.php';
require_once 'src/warehouse.php';
require_once 'src/defaults.php';
require_once 'src/kanban.php';
require_once 'src/engraving.php';
require_once 'src/production_losses.php';
require_once 'src/checklist.php';
require_once 'src/docs.php';
require_once 'src/wh_buffer.php';
require_once 'src/fastline.php';

$app->run();