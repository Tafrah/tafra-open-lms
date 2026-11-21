<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| public_html/index.php — للطريقة التانية في docs/install/shared-hosting.md
|--------------------------------------------------------------------------
| المشروع كله في مجلد برّا public_html (مثلاً ~/tafra-lms)، ومحتوى public/
| بس اتنسخ جوه public_html. انسخ الملف ده مكان public_html/index.php، وعدّل
| المسار في السطر اللي تحت لو اسم المجلد مختلف.
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$app_path = dirname(__DIR__).'/tafra-lms';

if (file_exists($maintenance = $app_path.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app_path.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $app_path.'/bootstrap/app.php';

// public_path() = public_html: الأصول المبنية (build/) ورابط storage بيتعملوا هنا
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
