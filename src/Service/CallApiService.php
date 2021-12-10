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

    public function getDepartmentData($department): array
    {
        return $this->getApi('live/departement/' . $department);
    }

    public function getAllDataByDate($date): array
    {
        return $this->getApi('departements-by-date/' . $date);
    }
    
    private function getApi(string $var): array
    {
        $response = $this->client->request(
            'GET',
            "https://coronavirusapifr.herokuapp.com/data/" . $var
        );
        return $response->toArray();
    }

    
}