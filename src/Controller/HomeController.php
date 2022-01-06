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
    public $france = [];
    public $japan = [];
    public $regions = [
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
    public $message = 'Nous n\'avons pas pu accéder à la source.';

    public function __construct(CallApiService $callApiService, CacheInterface $cache)
    {
        $this->france = $cache->get('result_france', function(ItemInterface $item) use($callApiService){

                            $item->expiresAt(new \DateTime('tomorrow'));
                            $france['data'] = $callApiService->getFrancedata();
                            $france['departments'] = $callApiService->getAllDepartmentData();
                            $day_befor = new \DateTime('-2 day now');
                            $france['data_day_before'] = $callApiService->getFranceDataByDate($day_befor->format("d-m-Y"));
                            
                            return $france;
                        });
        if (is_null($this->france['departments'])) {
            $this->france['region'] = $cache->get('result_region_france', function(ItemInterface $item) use($callApiService){
                                        $item->expiresAt(new \DateTime('tomorrow'));
                                            $france = [];
                                            foreach ($this->regions as $region) {
                                                $france[] = $callApiService->getRegionsByDate($region, (new DateTime('-2 days now'))->format("d-m-Y"));
                                            }
                                        return $france;
                                    });
        }
        $this->japan = $cache->get('result_japan', function(ItemInterface $item) use($callApiService){
                        $item->expiresAt(new \DateTime('tomorrow'));
                       
                        if(!is_null($callApiService->getAllDeathJap())) {
                            $japan['all_death'] = current($callApiService->getAllDeathJap());
                        } else {
                            $japan['all_death'] = $callApiService->getAllDeathJap();
                        }
                        if(!is_null($callApiService->getAllJap())) {
                            $japan['all'] = current($callApiService->getAllJap());
                        } else {
                            $japan['all'] = $callApiService->getAllDeathJap();
                        }

                        if(!is_null($callApiService->getAllJap())) {
                            $japan['all_hosp'] = current($callApiService->getAllHospJap());
                        } else {
                            $japan['all_hosp'] = $callApiService->getAllHospJap();
                        }
                        
                        return $japan;
                    });
    }
    /**
     * @Route("/", name="home")
     */
    public function index(CallApiService $callApiService, CacheInterface $cache, ChartService $chart): Response
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
            //changement l'ordre de la liste selon le numéro des departements.
            $regions  = array_column($this->france['departments'], 'reg');
            array_multisort($regions, SORT_ASC, $this->france['departments']);
            
            $outreMer = array_filter($this->france['departments'], function($element){
                return $element['reg'] < 7;
            });
            $auvergneRhoneAlpes = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 84;
            });
            $iledeFrance = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 11;
            });
            $bourgogneFC = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 27;
            });
            $normandie = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 28;
            });
            $hautsDeFrance = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 32;
            });
            $grandEst = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 44;
            });
            $paysDeLaLoire = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 52;
            });
            $bretagne = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 53;
            });
            $nouvelleAquitaine = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 75;
            });
            $occitanie = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 76;
            });
            $auvergneRhoneAlpes = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 84;
            });
            $provenceAlpesCoteAzur = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 93;
            });
            $corse = array_filter($this->france['departments'], function($element){
                return $element['reg'] == 94;
            });
           /*  $max = 0.9;
            $dangercount = array_filter($regionsTaux, function($element) use($max){
                return $element >= $max;
            }); */
            //les contenues pour mettre la data dans la graphique chart.
            $label= [];
            $hosp_departments = [];
            $rea_departments = [];
            $regions = [];
            $taux = [];

            foreach ($this->france['departments'] as $chart_departments) {
                $hosp_departments[] = $chart_departments['hosp'];
                $rea_departments[] = $chart_departments['rea'];
                $label[] = $chart_departments["lib_dep"];
                $regions[] = $chart_departments["reg"];
                $taux[] = ceil($chart_departments['TO'] * 100);
            }; 

            $label1 = array_slice($label, 0, 5);
            $hosp_departments1 =array_slice($hosp_departments, 0, 5);
            $rea_departments1 = array_slice($rea_departments, 0, 5);

            $label2 = array_slice($label,50,101);
            $hosp_departments2 =array_slice($hosp_departments,50,101);
            $rea_departments2 = array_slice($rea_departments,50, 101);
            $chart1 = $chart->chartBar(Chart::TYPE_BAR, $label1, $hosp_departments1, $rea_departments1, $taux);
            $chart2 = $chart->chartBar(Chart::TYPE_BAR, $label2, $hosp_departments2, $rea_departments2, $taux);
            
            
        }
        
        if (is_null($this->france['departments'])) {
            $regions = array_slice($this->france['region'],0,13);
            $OutreMer =array_slice($this->france['region'],13,18);

            $label= [];
            $hosp_departments = [];
            $rea_departments = [];
            $taux = [];

            foreach ($this->france['region'] as $chart_departments) {
                foreach ($chart_departments as $chart_region) {
                    $hosp_departments[] = $chart_region['hosp'];
                    $rea_departments[] = $chart_region['rea'];
                    $label[] = $chart_region["lib_dep"];
                    $taux[] = ceil($chart_region['TO'] * 100);
                }
                
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
            'data' => $this->france['data'],
            'france_diff' => $france_diff,
            'departments' => $this->france['departments'],
            'regions' => $regions,
            'outremers' => $OutreMer,
            'dataJap' => $this->japan['all'],
            'dataJapDeath' => $this->japan['all_death'],
            'dataJapHosp' => $this->japan['all_hosp'],
            'message' => $this->message,
            'chart1' => $chart1 ?? null,
            'chart2' => $chart2 ?? null,
        ]);
    }
}