<?php

namespace App\Controller;

use App\Service\CallApiService;
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
    /**
     * @Route("/", name="home")
     */
    public function index(CallApiService $callApiService, CacheInterface $cache, ChartBuilderInterface $chartBuilder): Response
    {
        
        $japan = $cache->get('result_japan', function(ItemInterface $item) use($callApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            $japan = [];
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
        $message = 'Nous n\'avons pas accédé à la source.';

        $france = $cache->get('result_france', function(ItemInterface $item) use($callApiService){
            $item->expiresAt(new \DateTime('tomorrow'));
            $france = [];
            $france['data'] = $callApiService->getFrancedata();
            $france['departments'] = $callApiService->getAllDepartmentData();
            $day_befor = new \DateTime('-2 day now');
            $france['data_day_before'] = $callApiService->getFranceDataByDate($day_befor->format("d-m-Y"));
            return $france;
        });

        $france_diff = [];
        $chart1 = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart2 = $chartBuilder->createChart(Chart::TYPE_BAR);


        if(!is_null($france['data']) && $france['data_day_before']) {
            $france_diff['death'] = $france['data'][0]['dc_tot'] - $france['data_day_before'][0]['dc_tot'];
            $france['data'][0]['TO'] = ceil($france['data'][0]['TO'] * 100);

            $label= [];
            $hosp_departments = [];
            $rea_departments = [];

            foreach ($france['departments'] as $chart_departments) {
                $label[] = $chart_departments['lib_dep'];
                $hosp_departments[] = $chart_departments['incid_hosp'];
                $rea_departments[] = $chart_departments['incid_rea'];
            }; 

            $label1 = array_slice($label, 0, 50);
            $hosp_departments1 =array_slice($hosp_departments, 0, 50);
            $rea_departments1 = array_slice($rea_departments, 0, 50);

            $label2 = array_slice($label,50,101);
            $hosp_departments2 =array_slice($hosp_departments,50,101);
            $rea_departments2 = array_slice($rea_departments,50, 101);
            
            $chart1->setData([
                'labels' => $label1,
                'borderWidth' => 1,
                'datasets' => [
                    [
                        'label' => 'Nouvelles Hospitalisations : ' . $france['departments'][0]['date'],
                        'backgroundColor' => 'rgb(255, 99, 132)',
                        'data' => $hosp_departments1,
                    ],
                    [
                        'label' => 'Nouvelles entrées en Réa : ' . $france['departments'][0]['date'],
                        'backgroundColor' => 'rgb(46, 41, 78)',
                        'data' => $rea_departments1,
                    ],
                ],
            ]);

            $chart1->setOptions([/* ... */]);
        
            $chart2->setData([
                'labels' => $label2,
                'borderWidth' => 1,
                'datasets' => [
                    [
                        'label' => 'Nouvelles Hospitalisations : ' . $france['departments'][0]['date'],
                        'backgroundColor' => 'rgb(255, 99, 132)',
                        'data' => $hosp_departments2,
                    ],
                    [
                        'label' => 'Nouvelles entrées en Réa : ' . $france['departments'][0]['date'],
                        'backgroundColor' => 'rgb(46, 41, 78)',
                        'data' => $rea_departments2,
                    ],
                ],
            ]);

            $chart2->setOptions([/* ... */]);
        }
    
        return $this->render('home/index.html.twig', [
            'data' => $france['data'],
            'france_diff' => $france_diff,
            'departments' => $france['departments'],
            'dataJap' => $japan['all'],
            'dataJapDeath' => $japan['all_death'],
            'dataJapHosp' => $japan['all_hosp'],
            'message' => $message,
            'chart1' => $chart1,
            'chart2' => $chart2,
        ]);
    }
}
