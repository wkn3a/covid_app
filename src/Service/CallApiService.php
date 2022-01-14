<?php
 
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CallApiService
{
    private $client;
    private $cache;

    public function __construct(HttpClientInterface $client, CacheInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }
    //France
    public function getFrancedata($time): ?array
    {
        return $this->getApi('live/France', 'france-live', $time);
    }

    public function getFranceDataByDate(string $date, $time): ?array
    {
        return $this->getApi('france-by-date/' . $date, 'france-by-date'. $date, $time);
    }

    /** 
     * Depertement
     * ne fonction pas bien. 04/01/2022, 06/01/2022,07/01/2022
    */
    public function getAllDepartmentLiveData($time): ?array
    {
        return $this->getApi('live/departements', 'departments-live',$time);
    }

    public function getAllDepartmentDataByDate($time, string $datePreci=null): ?array
    {
        if($datePreci) {
            return $this->getApi('departements-by-date/'. $datePreci, 'Alldepartements-by-date-Preci'. $datePreci, $time);
        } else {
        //Par précaution. Derniers 7 jours loop.
        $i = 1;
        while($i < 7) {
            $date = date('d-m-Y', strtotime('-'. $i . ' days')); 
            $dataDep = $this->getApi('departements-by-date/'. $date, 'Alldepartements-by-date'. $date, $time);
          if ($dataDep) {
            break;
          }
          $i++;
        }
            return $dataDep;
        }
    }

    public function getDepartmentDataByDate(string $department, string $date, $time): ?array
    {
        
        return $this->getApi('departement/' . $department . "/" . $date, 'department-by-date', $time);
    }

    /** 
     * ne fonction pas bien. 03/01/2022-
    
    public function getDepartmentDataLive($department): ?array
    {
        return $this->getApi('live/departement/' . $department, '');
       
    }*/

    /** 
     * ne fonction pas bien. 03/01/2022-
    
    public function getDepartmentData($department): ?array
    {
        return $this->getApi('departement/' . $department, '');
    }*/

   
    public function getRegionsByDate($region, $date, $time): ?array
    {
        return $this->getApi('region/' . $region . '/' . $date, 'regions_'. $region . $date, $time);
    }

    private function getApi(string $var, string $nomCache, $time): ?array
    {
        $response = $this->cache->get('result_' . $nomCache, function(ItemInterface $item) use($var, $time){
            $item->expiresAt($time);
            $response = $this->client->request(
                'GET',
                "https://coronavirusapifr.herokuapp.com/data/" . $var
            );
    
            if (200 !== $response->getStatusCode() ) {
                return null;
            }
    
            $header = $response->getHeaders();
    
            if ($header["content-type"][0] == "application/json; charset=utf-8"){
                return $response->toArray();
            } else {
                return null;
            }
        });
        return $response;
    }
}