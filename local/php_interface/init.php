<?php

use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('nightpilgrim.restapi');

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
}