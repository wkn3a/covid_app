<?php

namespace App\Controller;

use App\Service\CallApiService;
use App\Service\ChartService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\UX\Chartjs\Model\Chart;

class HomeController extends AbstractController
{
    private $allFrance = [];
    private $allFranceDaybefore = [];
    private $departemtFrance = [];
    private $japan = [];
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
    private $message = 'Nous n\'avons pas pu accéder à la source.';
    private const DATE = '04-01-2022';

    public function __construct(CallApiService $callApiService, CacheInterface $cache)
    {
        $this->allFrance = $callApiService->getFrancedata(); 
        $this->allFranceDaybefore = $callApiService->getFranceDataByDate(date('d-m-Y', strtotime('-2 days')));
        //Pour avoir la data par les departements. si ça ne marche pas les URL Api, return null.
        $this->departemtFrance = $callApiService->getAllDepartmentLiveData();
        if (is_null($this->departemtFrance)) {
            $this->departemtFrance = $callApiService->getAllDepartmentDataByDate();
        }
        if (is_null($this->departemtFrance)) {
            $this->departemtFrance = [];
            foreach ($this->regions as $region) {
                $this->departemtFrance[] = $callApiService->getRegionsByDate($region, date('d-m-Y', strtotime('-2 days')));
            }
        }//dd($this->departemtFrance);
        $this->france['departmentParDate'] = null;
        $tomorrow = new DateTime('tomorrow');
        
        $this->japan = $cache->get('result_japan', function(ItemInterface $item) use($callApiService, $tomorrow){
                        $item->expiresAt($tomorrow);
                       
                        if(!is_null($callApiService->getAllDeathJap())) {
                            $japan['all_death'] = $callApiService->getAllDeathJap();
                        } else {
                            $japan['all_death'] = null;
                        }

                        if(!is_null($callApiService->getAllJap())) {
                            $japan['all'] = $callApiService->getAllJap();
                        } else {
                            $japan['all'] = null;
                        }

                        if(!is_null($callApiService->getAllJap())) {
                            $japan['all_hosp'] = $callApiService->getAllHospJap();
                        } else {
                            $japan['all_hosp'] = null;
                        }
                        
                        return $japan;
                    }); 
    }
    /**
     * @Route("/", name="home")
     */
    public function index(ChartService $chart): Response
    {
        //Contene pour avoir $france_diff['death'] nombre de personne décedé en 24h.
        $france_diff = [];
        if(!is_null($this->allFrance) && $this->allFranceDaybefore) {

            //calcule (les décedés de jour - la veille).
            $france_diff['death'] = $this->allFrance[0]['dc_tot'] - $this->allFranceDaybefore[0]['dc_tot'];
            //Le taux d'occupation.
            $this->allFrance[0]['TO'] = ceil($this->allFrance[0]['TO'] * 100);
        }

        if (!is_null($this->departemtFrance)) {
            $label= [];
            $hosp_departments = [];
            $rea_departments = [];
            $regionsG = [];
            $taux = [];
            
            foreach ($this->departemtFrance as $chart_departments) {
                $hosp_departments[] = $chart_departments['hosp'];
                $rea_departments[] = $chart_departments['rea'];
                $label[] = $chart_departments["lib_dep"];
                $regionsG[] = $chart_departments["reg"];
                $taux[] = ceil($chart_departments['TO'] * 100);
            }; 

            $label1 = array_slice($label, 0, 50);
            $hosp_departments1 =array_slice($hosp_departments, 0, 50);
            $rea_departments1 = array_slice($rea_departments, 0, 50);

            $label2 = array_slice($label,50,101);
            $hosp_departments2 =array_slice($hosp_departments,50,101);
            $rea_departments2 = array_slice($rea_departments,50, 101);
            $chart1 = $chart->chartBar(Chart::TYPE_BAR, $label1, $hosp_departments1, $rea_departments1, $taux);
            $chart2 = $chart->chartBar(Chart::TYPE_BAR, $label2, $hosp_departments2, $rea_departments2, $taux);
        }

        if (is_null($this->departemtFrance) && !is_null($this->france['departmentParDate'])) {

            //grouper les departements pars la region.
            $regionsGroupe = self::groupBy($this->france['departmentParDate'], 'reg', false);
            $regions = array_slice($regionsGroupe,0,13);
            $outreMer =array_slice($regionsGroupe,13,18); 
            $dateRegions = $regions[0][0]['date'];

            $label= [];
            $hosp_departments = [];
            $rea_departments = [];
            $regionsG = [];
            $taux = [];
            
            foreach ($this->france['departmentParDate'] as $chart_departments) {
                $hosp_departments[] = $chart_departments['hosp'];
                $rea_departments[] = $chart_departments['rea'];
                $label[] = $chart_departments["lib_dep"];
                $regionsG[] = $chart_departments["reg"];
                $taux[] = ceil($chart_departments['TO'] * 100);
            }; 

            $label1 = array_slice($label, 0, 50);
            $hosp_departments1 =array_slice($hosp_departments, 0, 50);
            $rea_departments1 = array_slice($rea_departments, 0, 50);

            $label2 = array_slice($label,50,101);
            $hosp_departments2 =array_slice($hosp_departments,50,101);
            $rea_departments2 = array_slice($rea_departments,50, 101);
            $chart1 = $chart->chartBar(Chart::TYPE_BAR, $label1, $hosp_departments1, $rea_departments1, $taux);
            $chart2 = $chart->chartBar(Chart::TYPE_BAR, $label2, $hosp_departments2, $rea_departments2, $taux);
            
            
        }
        
        if (is_null($this->france['departmentParDate']) && is_null($this->departemtFrance)) {
             
            //Grouper les regions
            $regions = array_slice($this->france['region'],0,13);
            $outreMer =array_slice($this->france['region'],13,18);
            
            //Contenues pour les datas dans la graphique chart
            $label= [];
            $hosp_departments = [];
            $rea_departments = [];
            $taux = [];

            for ($i=0;$i<count($regions);$i++) {
                foreach ($regions[$i] as $chart_departments) {
                    $hosp_departments[] = $chart_departments['hosp'];
                    $rea_departments[] = $chart_departments['rea'];
                    $label[] = $chart_departments["lib_dep"];
                    $taux[] = ceil($chart_departments['TO'] * 100);
                }
            }
            for ($i=0;$i<count($outreMer);$i++) {
                foreach ($outreMer[$i] as $outreMers) {
                    $outreMerHosp[] = $outreMers['hosp'];
                    $outreMerIncidHosp[] = $outreMers['incid_hosp'];
                    $rea_outreMer[] = $outreMers['rea'];
                    $reaIncidoutreMer[] = $outreMers['incid_rea'];
                    $outreMertaux[] = ceil($outreMers['TO'] * 100);
                    $lib_dep[] = $outreMers["lib_dep"];
                }
            }

            $dateRegions = $regions[0][0]['date'];

            //Diviser les datas en deux parties.
            $label1 = array_slice($label, 0, 50);
            $hosp_departments1 =array_slice($hosp_departments, 0, 50);
            $rea_departments1 = array_slice($rea_departments, 0, 50);

            $label2 = array_slice($label,50,101);
            $hosp_departments2 =array_slice($hosp_departments,50,101);
            $rea_departments2 = array_slice($rea_departments,50, 101);

            $chart1 = $chart->chartBar(Chart::TYPE_BAR, $label1, $hosp_departments1, $rea_departments1);
            $chart2 = $chart->chartBar(Chart::TYPE_BAR, $label2, $hosp_departments2, $rea_departments2);
            
        }

        //Japon
        if (!is_null($this->japan['all_death']) && $this->japan['all_hosp'] ) {

            //Calcul pour le numbre de mort en 24h.
            $jaDeathOneDay = array_slice($this->japan['all_death'], 0,2);
            $jaDeathOneDay = $jaDeathOneDay[0]['ndeaths'] - $jaDeathOneDay[1]['ndeaths'];

            $jaHospOneDay = array_slice($this->japan['all_hosp'], 0,2);
            $jaHospOneDay = $jaHospOneDay[0]['ncures'] - $jaHospOneDay[1]['ncures'];
            
        }
        return $this->render('home/index.html.twig', [
            'dataAllFrance' => $this->allFrance,
            'france_diff' => $france_diff ?? null,
            'dataDepartments' => $this->departemtFrance,
            'dataregions' => $regions ?? null,
            'dateRegion' => $dateRegions ?? null,
            'dataoutremers' => $outreMer ?? null,
            'dataJap' => current($this->japan['all']),
            'dataJapDeath' => current($this->japan['all_death']),
            'dataJapDeath24' => $jaDeathOneDay ?? null,
            'dataJapHosp' => current($this->japan['all_hosp']),
            'dataJapHosp24' => $jaHospOneDay ?? null,
            'message' => $this->message,
            'chart1' => $chart1 ?? null,
            'chart2' => $chart2 ?? null,
        ]);
    }

    static public function groupBy(array $list, string $key, bool $double) {
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