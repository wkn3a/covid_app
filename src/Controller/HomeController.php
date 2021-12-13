<?php

namespace App\Controller;

use App\Service\CallApiService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(CallApiService $callApiService): Response
    {
        $date = New DateTime();
        $dataJapDeath = $callApiService->getAllDeathJap();
        $dataJap = $callApiService->getAllJap();
        $dataJapHosp = $callApiService->getAllHospJap();

        return $this->render('home/index.html.twig', [
            'data' => $callApiService->getFrancedata(),
            'departments' => $callApiService->getAllDepartmentData(),
            'dataJap' => current($dataJap),
            'dataJapDeath' => current($dataJapDeath),
            'dataJapHosp' => current($dataJapHosp)
        ]);
    }
}
