<?php

namespace HAR;

require_once '../vendor/autoload.php';

define('EASE_LOGGER', 'console|syslog');

$itemid = 0;

$downloader = new Har();
$downloader->debug = false;
$downloader->logBanner();

do {
    $downloader->obtain(++$itemid);
} while (true);
