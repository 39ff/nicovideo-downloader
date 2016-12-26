<?php
require '../vendor/autoload.php';
require 'autoload.php';

use NicoDL\Downloader;

try {
    $to = new Downloader();
    $to->login($argv[1], $argv[2])->getTimeShiftTicket($argv[3]);
    $result = $to->getTimeShiftPublishURL($argv[3]);
    $command = $to->getRtmpCommand($result);

    foreach ($command as $cmd){
        echo $cmd.PHP_EOL;
    }

}catch(\NicoDL\DownloaderException $e){
    echo 'error : ' .($e->getMessage());
}catch(Exception $e){
    echo 'error : ' .($e->getMessage());
}
