<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\API\Router\Router;
use App\Config;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/helpers.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Run router with complete middleware pipeline
$router = new Router();
$response = $router->handle();

echo json_encode($response);
