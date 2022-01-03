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
        $data_chart =$cache->get('result_department_detail_7j_' . $department, function(ItemInterface $item) use($callApiService, $department){
                        $item->expiresAt(new \DateTime('tomorrow')); 
            $item->expiresAt(new \DateTime('tomorrow'));
                        $item->expiresAt(new \DateTime('tomorrow')); 
                        $datas = [];
                        for ($i=1; $i < 8; $i++) {
                            $day = date('d-m-Y', strtotime('-'. $i . ' days'));
                            $datas[] = $callApiService->getDepartmentDataByDate($department, $day);
                        };
                        return $datas;
        });

        $datas = [];
        foreach ($data_chart as $key => $data) {
            $datas[] = $data[0];
        };

        $data_dep = $cache->get('result_department_' . $department, function(ItemInterface $item) use($callApiService, $department){
                $item->expiresAt(new \DateTime('tomorrow'));
                $day_befor = new \DateTime('yesterday');
                $data = $callApiService->getDepartmentDataByDate($department, $day_befor->format("d-m-Y"));
                return $data;
        });

        $label = [];
        $hospitalisation = [];
        $rea = [];

        foreach ($datas as $data) {
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

        return $this->render('department/index.html.twig', [
            'data_dep' => $data_dep,
            'chart' => $chart,
        ]);
    }
}
