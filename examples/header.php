<?php
ob_start();
?>
<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <link href="../skin/core/default/css/bootstrap_dark.css" rel="stylesheet" type="text/css"/>
        <link href="../skin/core/default/css/elusive-webfont.css" rel="stylesheet" type="text/css"/>
        <link href="../skin/core/default/css/elusive-webfont-ie7.css" rel="stylesheet" type="text/css"/>
        <link href="../skin/core/default/css/base.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
<?php
use Core\Benchmark\Helper as Performance;
require_once '../Lib/loader.php';
new Loader(dirname(__FILE__));
Performance\Benchmark::start();
Performance\Benchmark::setMarker('start');
?>