<?php

namespace App\Controller;

use App\Service\CallApiService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(CallApiService $callApiService, CacheInterface $cache): Response
    {
        
        $japan = $cache->get('result_japan', function(ItemInterface $item) use($callApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            $japan = [];
            $japan['all_death'] = $callApiService->getAllDeathJap();
            $japan['all'] = $callApiService->getAllJap();
            $japan['all_hosp'] = $callApiService->getAllHospJap();
            return $japan;
        });

        $france = $cache->get('result_france', function(ItemInterface $item) use($callApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            $france = [];
            $france['data'] = $callApiService->getFrancedata();
            $france['departments'] = $callApiService->getAllDepartmentData();
            return $france;
        });

        return $this->render('home/index.html.twig', [
            'data' => $france['data'],
            'departments' => $france['departments'],
            'dataJap' => current($japan['all']),
            'dataJapDeath' => current($japan['all_death']),
            'dataJapHosp' => current($japan['all_hosp'])
        ]);
    }
}
