<?php

namespace App\Controller;

use App\Service\CallApiService;
use DateTime;
use PhpParser\Node\Stmt\Label;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class DepartmentController extends AbstractController
{
    /**
     * @Route("/department/{department}", name="app_department")
     */
    public function index(string $department, CallApiService $callApiService, ChartBuilderInterface $chartBuilder, CacheInterface $cache): Response
    {
        $data_chart = $cache->get('result_department_detail_7j_' . $department, function(ItemInterface $item) use($callApiService, $department){
            $item->expiresAt(new \DateTime('tomorrow'));
            $datas = $callApiService->getDepartmentData($department);
            $datas = array_reverse($datas);
            $datas = array_slice($datas, 0, 7);
            return $datas;
        });

        $label = [];
        $hospitalisation = [];
        $rea = [];

        foreach ($data_chart as $data) {
            $label[] = $data['date'];
            $hospitalisation[] = $data['incid_hosp'];
            $rea[] = $data['incid_rea'];
        }; 

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => array_reverse($label),
            'datasets' => [
                [
                    'label' => 'Nouvelles Hospitalisations',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => array_reverse($hospitalisation),
                ],
                [
                    'label' => 'Nouvelles entrées en Réa',
                    'borderColor' => 'rgb(46, 41, 78)',
                    'data' => array_reverse($rea),
                ],
            ],
        ]);

        $chart->setOptions([/* ... */]);

        $data = $cache->get('result_department_france_'. $department, function(ItemInterface $item) use($callApiService, $department){
            $item->expiresAt(new \DateTime('tomorrow'));
            return  $callApiService->getDepartmentDataLive($department);
        }); 
        return $this->render('department/index.html.twig', [
            'data' => $data,
            'chart' => $chart,
        ]);
    }
}
