<?php

namespace App\Controller;

use App\Service\CallApiService;
use App\Service\ChartService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Model\Chart;

class HomeController extends AbstractController
{
    private $allFrance = [];
    private $allFranceDaybefore = [];
    private $departemtFrance = [];
    private $regions = [
                        'Auvergne et Rhône-Alpes', 
                        'Bourgogne et Franche-Comté', 
                        'Bretagne',
                        'Centre-Val de Loire',
                        'Corse',
                        'Grand Est',
                        'Hauts-de-France',
                        'Île-de-France',
                        'Normandie',
                        'Nouvelle Aquitaine',
                        'Occitanie',
                        'Pays de la Loire',
                        'Provence-Alpes-Côte d\'Azur',
                        'Guadeloupe',
                        'Martinique',
                        'Guyane',
                        'Réunion',
                        'Mayotte',
                    ];
    private $message = 'Nous n\'avons pas réussi à accéder à la source.';
    private $tempExpire;
    private $hier;

   
    public function __construct(CallApiService $callApiService)
    {
        $this->tempExpire = new DateTime('tomorrow');
        $this->hier = date('d-m-Y', strtotime('-1 days'));
        $this->allFrance = $callApiService->getFrancedata($this->tempExpire); 
        $this->allFranceDaybefore = $callApiService->getFranceDataByDate(date('d-m-Y', strtotime('-2 days')), $this->tempExpire);
        
        //Pour avoir la data par les departements. si ça ne marche pas les URL Api, return null.
        $this->departemtFrance = $callApiService->getAllDepartmentLiveData($this->tempExpire);
        if (is_null($this->departemtFrance)) {
            $this->departemtFrance = $callApiService->getAllDepartmentDataByDate($this->tempExpire);
        }
        if (is_null($this->departemtFrance)) {
            $departemtFrance = [];
            foreach ($this->regions as $region) {
                $departemtFrance[] = $callApiService->getRegionsByDate($region, $this->hier, $this->tempExpire);
            }
            $this->departemtFrance = [];
            for ($i=0; $i<count($departemtFrance); $i++) {
                foreach ($departemtFrance[$i] as $val) {
                $this->departemtFrance[] = $val;
                }
            }
        }

    }

     /**
     * @Route("/", name="home")
     */
    public function index(ChartService $chart): Response
    {
        
        //Contene pour avoir $france_diff['death'] nombre de personne décedé en 24h.
        if(!is_null($this->allFrance) && !is_null($this->allFranceDaybefore)) {
            //calcule (les décedés de jour - la veille).
            $france_diff = $this->allFrance[0]['dc_tot'] - $this->allFranceDaybefore[0]['dc_tot'];
            //Le taux d'occupation.
            $this->allFrance[0]['TO'] = ceil($this->allFrance[0]['TO'] * 100);
        }
        //101departements quand c'est appelé par getAllDepartmentLiveData() et getAllDepartmentDataByDate().
        if (!is_null($this->departemtFrance)) {
            //Grouper les departements pars la region.
            $regionsGroupe = self::groupBy($this->departemtFrance, 'reg', false); 

            $label= [];
            $hosp_departments = [];
            $rea_departments = [];

            //Découpage en 2.
            $regions = array_slice($regionsGroupe, 0, 13);
            $outreMer =array_slice($regionsGroupe, 13, 18); 

            foreach ($this->departemtFrance as $chart_departments) {
                $hosp_departments[] = $chart_departments['hosp'];
                $rea_departments[] = $chart_departments['rea'];
                $label[] = $chart_departments["lib_dep"];
            };
            
            $label1 = array_slice($label, 0, 50);
            $hosp_departments1 =array_slice($hosp_departments, 0, 50);
            $rea_departments1 = array_slice($rea_departments, 0, 50);

            $label2 = array_slice($label,50,101);
            $hosp_departments2 =array_slice($hosp_departments,50,101);
            $rea_departments2 = array_slice($rea_departments,50, 101);
            $chart1 = $chart->chartBar(Chart::TYPE_BAR, $label1, $hosp_departments1, $rea_departments1);
            $chart2 = $chart->chartBar(Chart::TYPE_BAR, $label2, $hosp_departments2, $rea_departments2);
        }
       
        return $this->render('home/index.html.twig', [
            'hier' => $this->hier,
            'dataAllFrance' => $this->allFrance,
            'france_diff' => $france_diff ?? null,
            'dataDepartments' => $this->departemtFrance,
            'dataRegions' => $regions ?? null,
            'dataOutremers' => $outreMer ?? null,
            'message' => $this->message,
            'chart1' => $chart1 ?? null,
            'chart2' => $chart2 ?? null,
            'menu_actu' => 'home'
        ]);
    }

    public static function groupBy(array $list, string $key, bool $double) {
        $newArray = [];

        if (is_array($list) && $double) {
            for ($i=0; $i<count($list); $i++) {
                    foreach ($list[$i] as $val) {
                    $newArray[$val[$key]][] = $val;
                }
            }
        } else {
            foreach ($list as $val) {
                    $newArray[$val[$key]][] = $val;
            }
        }

        return $newArray;
    }
}