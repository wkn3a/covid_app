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
    private $france = [];
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
    const DATE = '04-01-2022';

    public function __construct(CallApiService $callApiService, CacheInterface $cache)
    {
        $tomorrow = new DateTime('tomorrow');
        $this->france = $cache->get('result_france', function(ItemInterface $item) use($callApiService ,$tomorrow){
                            $item->expiresAt($tomorrow);
                            $france['data'] = $callApiService->getFrancedata();
                            $france['departments'] = $callApiService->getAllDepartmentData();
                            $day_befor = new \DateTime('-2 day now');
                            $france['data_day_before'] = $callApiService->getFranceDataByDate($day_befor->format("d-m-Y"));
                            $france['departmentParDate'] = null;
                            return $france;
                        });
        if (is_null($this->france['departments'])) {
            $this->france['departmentParDate'] = $cache->get('result_france_all_departements', function(ItemInterface $item) use($callApiService ,$tomorrow){
                                                    $item->expiresAt($tomorrow);
                                                    $france = $callApiService->getAllDepartmentDataByDate(self::DATE);
                                                    return $france;
                                                });
        }

        if (is_null($this->france['departments']) && is_null($this->france['departmentParDate'])) {
            $this->france['region'] = $cache->get('result_region_france', function(ItemInterface $item) use($callApiService, $tomorrow){
                                        $item->expiresAt($tomorrow);
                                            $france = [];
                                            foreach ($this->regions as $region) {
                                                $france[] = $callApiService->getRegionsByDate($region, self::DATE /* (new DateTime('-2 days now'))->format("d-m-Y") */);
                                            }
                                        return $france;
                                    });
        }
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
        if(!is_null($this->france['data']) && $this->france['data_day_before']) {

            //calcule (les décedés de jour - la veille).
            $france_diff['death'] = $this->france['data'][0]['dc_tot'] - $this->france['data_day_before'][0]['dc_tot'];
            //Le taux d'occupation.
            $this->france['data'][0]['TO'] = ceil($this->france['data'][0]['TO'] * 100);
        }

        if (!is_null($this->france['departments'])) {
            $label= [];
            $hosp_departments = [];
            $rea_departments = [];
            $regionsG = [];
            $taux = [];
            
            foreach ($this->france['departments'] as $chart_departments) {
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

        if (is_null($this->france['departments']) && !is_null($this->france['departmentParDate'])) {

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
        
        if (is_null($this->france['departmentParDate']) && is_null($this->france['departments'])) {
             
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
            'data' => $this->france['data'],
            'france_diff' => $france_diff,
            'departments' => $this->france['departments'],
            'regions' => $regions ?? null,
            'dateRegion' => $dateRegions ?? null,
            'outremers' => $outreMer ?? null,
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