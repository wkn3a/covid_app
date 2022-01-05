<?php

namespace App\Controller;

use App\Service\CallApiService;
use App\Service\Functions;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class HomeController extends AbstractController
{
    public $france = [];
    public $japan = [];
    public $region;
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
    public function index(CallApiService $callApiService, CacheInterface $cache, Functions $functions): Response
    {
        $france_diff = [];

       /*  $chart1 = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart2 = $chartBuilder->createChart(Chart::TYPE_BAR);
 */

        if(!is_null($this->france['data']) && $this->france['data_day_before'] && !is_null($this->france['departments'])) {

            $france_diff['death'] = $this->france['data'][0]['dc_tot'] - $this->france['data_day_before'][0]['dc_tot'];
            $this->france['data'][0]['TO'] = ceil($this->france['data'][0]['TO'] * 100);
 
            $label= [];
            $hosp_departments = [];
            $rea_departments = [];

            foreach ($this->france['departments'] as $chart_departments) {
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

            $chart1 = $functions->chartBar(Chart::TYPE_BAR, $label1, $hosp_departments1, $rea_departments1);
            $chart2 = $functions->chartBar(Chart::TYPE_BAR, $label2, $hosp_departments2, $rea_departments2);
            
            
        } 

        return $this->render('home/index.html.twig', [
            'data' => $this->france['data'],
            'france_diff' => $france_diff,
            'departments' => $this->france['departments'],
            'dataJap' => $this->japan['all'],
            'dataJapDeath' => $this->japan['all_death'],
            'dataJapHosp' => $this->japan['all_hosp'],
            'message' => $this->message,
            'chart1' => $chart1,
            'chart2' => $chart2,
        ]);
    }
}