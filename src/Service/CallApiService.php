<?php
 
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\Functions;
use DateTime;

use function PHPUnit\Framework\isJson;

class CallApiService
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getFrancedata(): ?array
    {
        return $this->getApi('live/France');
    }

    /** 
     * ne fonction pas bien. 04/01/2022
    */
    public function getAllDepartmentData(): ?array
    {
        return $this->getApi('live/departements');
    }

    public function getAllDepartmentDataByDate(): ?array
    {
        $day = new DateTime('yesterday');
        return $this->getApi('departements-by-date/'. $day->format("d-m-Y"));
    }

    /** 
     * ne fonction pas bien. 03/01/2022
    */
    public function getDepartmentDataLive($department): ?array
    {
        return $this->getApi('live/departement/' . $department);
       
    }

    /** 
     * ne fonction pas bien. 03/01/2022
    */
    public function getDepartmentData($department): ?array
    {
        return $this->getApi('departement/' . $department);
    }

    public function getDepartmentDataByDate($department, $date): ?array
    {
        return $this->getApi('departement/' . $department . "/" . $date);
    }

    public function getAllDataByDate($date): ?array
    {
        return $this->getApi('departements-by-date/' . $date);
    }

    public function getFranceDataByDate($date): ?array
    {
        return $this->getApi('france-by-date/' . $date);
    }
    
    private function getApi(string $var): ?array
    {
        $response = $this->client->request(
            'GET',
            "https://coronavirusapifr.herokuapp.com/data/" . $var
        );

        $header = $response->getHeaders();

        if (200 !== $response->getStatusCode() ) {
            return null;
        }
        if ($header["content-type"][0] == "application/json; charset=utf-8"){
            return $response->toArray();
        } else {
            return null;
        }
    }

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