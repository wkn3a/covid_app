<?php
 
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTime;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CallApiService
{
    private $client;
    private $cache;
    private $tomorrow;

    public function __construct(HttpClientInterface $client, CacheInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->tommorow = new DateTime('tomorrow');
    }
    //France
    public function getFrancedata(): ?array
    {
        return $this->getApi('live/France', 'france-live');
    }

    public function getFranceDataByDate(string $date): ?array
    {
        return $this->getApi('france-by-date/' . $date, 'france-by-date');
    }

    /** 
     * Depertement
     * ne fonction pas bien. 04/01/2022, 06/01/2022,07/01/2022
    */
    public function getAllDepartmentLiveData(): ?array
    {
        return $this->getApi('live/departements', 'departments-live');
    }

    public function getAllDepartmentDataByDate(string $datePreci=null): ?array
    {
        if($datePreci) {
            return $this->getApi('departements-by-date/'. $datePreci, 'Alldepartements-by-date-Preci');
        } else {
        //Par précaution. Derniers 7 jours loop.
        $i = 1;
        while($i < 7) {
            $date = date('d-m-Y', strtotime('-'. $i . ' days')); 
            $dataDep = $this->getApi('departements-by-date/'. $date, 'Alldepartements-by-date');
          if ($dataDep) {
            break;
          }
          $i++;
        }
            return $dataDep;
        }
    }

    public function getDepartmentDataByDate(string $department, string $date): ?array
    {
        
        return $this->getApi('departement/' . $department . "/" . $date, 'department-by-date');
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

   
    public function getRegionsByDate($region, $date): ?array
    {
        return $this->getApi('region/' . $region . '/' . $date, 'regions_'. $region);
    }

    private function getApi(string $var, string $nomCache): ?array
    {
        $response = $this->cache->get('result_' . $nomCache, function(ItemInterface $item) use($var){
            $item->expiresAt($this->tomorrow);
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
    //Japon
    public function getAllJap(): ?array
    {
        return $this->getApiJap('-npatients.json');
    }

    public function getAllDeathJap(): ?array
    {
        return $this->getApiJap('-ndeaths.json');
    }

    public function getAllHospJap(): ?array
    {
        return $this->getApiJap('-ncures.json');
    }
   

    private function getApiJap($var): ?array
    {
        $response = $this->client->request(
            'GET',
            "https://data.corona.go.jp/converted-json/covid19japan" . $var 
        );
        if (200 !== $response->getStatusCode()) {
            return null;
        } else {
            $data = $response->toArray();
            arsort($data);
            return  $data;
        }
    }

    public function getApiJapDeath($date): ?array
    {
        $response = $this->client->request(
            'GET',
            "https://opendata.corona.go.jp/api/Covid19JapanNdeaths?date=" . $date
        );
        if (200 !== $response->getStatusCode()) {
            return null;
        } else {
            return $response->toArray();
        }
    }
}