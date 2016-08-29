<?php
namespace NicoDL;
class Downloader{

    private $client;

    public function ReservedTicketList(){
        $response = $this->client->request('GET', 'http://live.nicovideo.jp/api/watchingreservation', [
            'query'=>[
                'mode'=>'list',
            ]
        ]);
        self::checkXML((string)$response->getBody());

        return $this->xml;

    }

    private function overrideTimeShift($lv){
        $response = $this->client->request('GET', 'http://live.nicovideo.jp/api/watchingreservation', [
            'query'=>[
                'mode'=>'confirm_watch_my',
                'vid'=>$lv,
                'next_url',
                'analytic'
            ]
        ]);
        self::checkXML((string)$response->getBody());

    }

    private function getTimeShiftReserveToken($response){


        if(preg_match('@Nicolive.WatchingReservation.auto_register\(\'([0-9]++)\',\s\'([a-zA-Z0-9_]++)@',$response,$m)){
            return ['mode'=>'auto_register','token'=>$m[2]];
        }

        if(preg_match('@Nicolive.TimeshiftActions.doRegister\(\'([0-9]++)\',\s\'([a-zA-Z0-9_]++)@',$response,$m)){
            return ['mode'=>'overwrite','token'=>$m[2]];
        }

        if(preg_match('@Nicolive.TimeshiftActions.confirmToWatch\(\'([0-9]++)\',\s\'([a-zA-Z0-9_]++)@',$response,$m)){
            return ['mode'=>'none','token'=>NULL];
        }

        if(preg_match('@Nicolive.TimeshiftActions.moveWatch@',$response,$m)){
            return ['mode'=>'none','token'=>NULL];
        }


       throw new DownloaderException('Failed to parse TimeShiftReserveToken : '.$response);

    }

    public function getTimeShiftTicket($lv){


        $response = $this->client->request('GET', 'http://live.nicovideo.jp/api/watchingreservation', [
            'query'=>[
                'mode'=>'watch_num',
                'vid'=>str_replace('lv','',$lv),
                'next_url',
                'analytic'
            ]
        ]);


        $reserveInfo = self::getTimeShiftReserveToken((string)$response->getBody());

        if(strcmp($reserveInfo['mode'],'none') === 0){
            return true;
        }

        $response = $this->client->request('POST', 'http://live.nicovideo.jp/api/watchingreservation', [
            'query'=>[
                'mode'=>$reserveInfo['mode'],
                'vid'=>str_replace('lv','',$lv),
                'token'=>$reserveInfo['token']
            ]
        ]);

        return (string)$response->getBody();

    }

    public function checkXML($response){

        $xml = simplexml_load_string($response);

        if($xml === FALSE){
            throw new DownloaderException('Failed to parse xml');
        }

        if(strcmp($xml->attributes()->status,'ok') !== 0){
            throw new DownloaderException($xml->error->code);
        }
        $this->xml = $xml;

        return $this;
    }

    public function getRtmpCommand(array $results){
        $count = 1;
       foreach($results['queue'] as $r){
           $explode = explode(' ',$r);
           $rtmp[] = 'rtmpdump -r '.$results['rtmpURL'].' -y mp4:'.$explode[2].' -C S:'.$results['ticket'].' -e -o '.$results['lv'].'-'.$count.'.flv';
           $count++;

       }
        return $rtmp;
    }
    public function getTimeShiftPublishURL($lv){
        $response = $this->client->request('GET', 'http://watch.live.nicovideo.jp/api/getplayerstatus', [
            'query'=>[
                'v'=>$lv
            ]
        ]);
        self::checkXML((string)$response->getBody());

        $queue = [];
        $rtmpURL = $this->xml->rtmp->url;
        foreach($this->xml->stream->quesheet->que as $que){

            if(preg_match("/^\/publish/",$que)){
               $queue[] = $que;
            }

        }

        if(empty($queue) or empty($rtmpURL)){
            throw new DownloaderException('Failed to get PublishURL :'.(string)$response->getBody());
        }

        return ['queue'=>$queue,'rtmpURL'=>$rtmpURL,'ticket'=>$this->xml->rtmp->ticket,'lv'=>$this->xml->stream->id];

    }

    public function login($username,$password){
        $response  = $this->client->request('POST','https://account.nicovideo.jp/api/v1/login',[
            'form_params'=>[
                'mail_tel'=>$username,
                'password'=>$password
            ]
        ]);
        return (string)$response->getBody();

    }

    public function __construct(GuzzleHttp\ClientInterface $client =  null){

        if(!isset($client)){
            $client = new \GuzzleHttp\Client(
                [
                    'cookies' => true,
                    'headers' => [
                        'Accept' => '*/*',
                        'Accept-Language' => 'ja;q=1, en;q=0.9, fr;q=0.8, de;q=0.7, zh-Hans;q=0.6, zh-Hant;q=0.5',
                    ]
                ]);
        }
        if($client instanceof \GuzzleHttp\Client === FALSE){
            throw new DownloaderException("Failed to load Guzzle");
        }

        $this->client = $client;
        return $this;
    }


    public function __get($name){
        $name = filter_var($name);
        if(!property_exists($this,$name)){
            throw new \OutOfRangeException('Invalid property : '.$name);
        }
        return $this->$name;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
        return $this;
    }


}
