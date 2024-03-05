<?php

namespace HAR;

use Garden\Cli\Cli;

require_once '../vendor/autoload.php';

define('EASE_LOGGER', 'console|syslog');

// Define the cli options.
$cli = new Cli();

$cli->description('Download Himalayan Art images in full quality.')
        ->opt('item:i', 'item ID to download', true, 'integer')
        ->opt('destination:d', 'destination directory', false)
        ->opt('verbose:v', 'verbose output', false)
        ->command('get')
        ->description('get only one image')
        ->command('pull')
        ->description('try to download all images');

// Parse and return cli args.
$args = $cli->parse($argv, true);

\Ease\Shared::init([], file_exists('.env') ? '.env' : '');

$itemid = $args->getOpt('item', \Ease\Shared::cfg('HAR_ITEM_ID', 1));

$downloader = new Har($args->getOpt('destination', \Ease\Shared::cfg('HAR_DONE_DIR', './')));
$downloader->debug = $args->getOpt('verbose', \Ease\Shared::cfg('HAR_DEBUG', false));
if ($downloader->debug) {
    $downloader->logBanner();
}

$command = $args->getCommand();

switch ($command) {
    case 'get':
        $downloader->obtain($itemid);
        break;
    default:
        do {
            $downloader->obtain($itemid++);
        } while (true);
        break;
}
