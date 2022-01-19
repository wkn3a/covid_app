<?php

namespace App\Controller;

use App\Service\CallApiJapanService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JapanController extends AbstractController
{
    private $allJapan = [];
    private $allDeathJapan = [];
    private $allHospJapan = [];
    private $tempExpire;
    private $message = 'Nous n\'avons pas réussi à accéder à la source.';
   
    public function __construct(CallApiJapanService $callApiJapanService)
    {
        $this->tempExpire = new DateTime('tomorrow');
        $this->allJapan = $callApiJapanService->getAllJap($this->tempExpire);
        $this->allDeathJapan = $callApiJapanService->getAllDeathJap($this->tempExpire); 
        $this->allHospJapan = $callApiJapanService->getAllHospJap($this->tempExpire); //dd($this->allDeathJapan, $this->allJapan, $this->allHospJapan);
    }

     /**
     * @Route("/japan", name="japan")
     */
    public function index(): Response
    {
         if (!is_null($this->allDeathJapan) && $this->allHospJapan) {
            //Calcul pour le numbre de mort en 24h.
            $jaDeathOneDay = $this->allDeathJapan[1]['ndeaths'] - $this->allDeathJapan[0]['ndeaths'];
            $jaHospOneDay = $this->allHospJapan[1]['ncures'] - $this->allHospJapan[0]['ncures'];
        }
        return $this->render('japan/index.html.twig', [
            'dataJap' => $this->allJapan[1] ?? null,
            'dataJapDeath' => $this->allDeathJapan[1] ?? null,
            'dataJapDeath24' => $jaDeathOneDay ?? null,
            'dataJapHosp' => $this->allHospJapan[1] ?? null,
            'dataJapHosp24' => $jaHospOneDay ?? null,
            'message' => $this->message,
            'menu_actu' => 'japan'
        ]);
    }
}
