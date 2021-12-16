<?php
 
namespace App\Service;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CallApiService
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getFrancedata(): array
    {
        return $this->getApi('live/France');
    }

    public function getAllDepartmentData(): array
    {
        return $this->getApi('live/departements');
    }

    public function getDepartmentDataLive($department): array
    {
        return $this->getApi('live/departement/' . $department);
    }

    public function getDepartmentData($department): array
    {
        return $this->getApi('departement/' . $department);
    }

    public function getAllDataByDate($date): array
    {
        return $this->getApi('departements-by-date/' . $date);
    }

    public function getFranceDataByDate($date): array
    {
        return $this->getApi('france-by-date/' . $date);
    }
    
    private function getApi(string $var): array
    {
        $response = $this->client->request(
            'GET',
            "https://coronavirusapifr.herokuapp.com/data/" . $var
        );
        return $response->toArray();
    }

    public function getAllJap(): array
    {
        return $this->getApiJap('-npatients.json');
    }

    public function getAllDeathJap(): array
    {
        return $this->getApiJap('-ndeaths.json');
    }

    public function getAllHospJap(): array
    {
        return $this->getApiJap('-ncures.json');
    }
   

    private function getApiJap($var): array
    {
        $response = $this->client->request(
            'GET',
            "https://data.corona.go.jp/converted-json/covid19japan" . $var 
        );
        $data = $response->toArray();
        arsort($data);
        return  $data;
    }

    public function getApiJapDeath($date): array
    {
        $response = $this->client->request(
            'GET',
            "https://opendata.corona.go.jp/api/Covid19JapanNdeaths?date=" . $date
        );
        return $response->toArray();
    }
}