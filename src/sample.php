<?php
require '../vendor/autoload.php';
require 'autoload.php';

use NicoDL\Downloader;

try {
    $to = new Downloader();
    $to->login('email', 'password');
    $to->getTimeShiftTicket('lv273112241');
    $result = $to->getTimeShiftPublishURL('lv273112241');
    $command = $to->getRtmpCommand($result);

    print_r($command);

}catch(\NicoDL\DownloaderException $e){
    echo 'error : ' .($e->getMessage());
}catch(Exception $e){
    echo 'error : ' .($e->getMessage());
}
