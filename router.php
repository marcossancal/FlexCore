<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$file = __DIR__ . $uri;
if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

require_once __DIR__ . '/index.php';