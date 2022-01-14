<?php
 
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CallApiJapanService
{
    private $client;
    private $cache;

    public function __construct(HttpClientInterface $client, CacheInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }
    //Japon
    public function getAllJap($time): ?array
    {
        return $this->getApiJap('-npatients.json', $time);
    }

    public function getAllDeathJap($time): ?array
    {
        return $this->getApiJap('-ndeaths.json', $time);
    }

    public function getAllHospJap($time): ?array
    {
        return $this->getApiJap('-ncures.json', $time);
    }
   

    private function getApiJap($var,$time): ?array
    {
        $response = $this->cache->get('result_' . $var, function(ItemInterface $item) use($var, $time){
                        $item->expiresAt($time);
                        $response = $this->client->request(
                            'GET',
                            "https://data.corona.go.jp/converted-json/covid19japan" . $var
                        );
                        if (200 !== $response->getStatusCode() ) {
                            return null;
                        }
                
                        $header = $response->getHeaders();
                
                        if ($header["content-type"][0] == "application/json"){
                            $dataDeuxDernier = [];
                            $data = $response->toArray();
                            $dataDeuxDernier[] = $data[count($data)-2];
                            $dataDeuxDernier[] = $data[count($data)-1];
                            return $dataDeuxDernier;
                        } else {
                            return null;
                        }
                    });
        return $response;
    }

    public function getApiJapDeathParDate($date, $time): ?array
    {
        $response = $this->cache->get('result_deth_japon' . $date, function(ItemInterface $item) use($date, $time){
                    $item->expiresAt($time);
                    $response = $this->client->request(
                        'GET',
                        "https://opendata.corona.go.jp/api/Covid19JapanNdeaths?date=" . $date
                    );
                    if (200 !== $response->getStatusCode() ) {
                        return null;
                    }
            
                    $header = $response->getHeaders();
            
                    if ($header["content-type"][0] == "application/json"){
                        return  $response->toArray();
                    } else {
                        return null;
                    }
                });
        return $response;
    }
}