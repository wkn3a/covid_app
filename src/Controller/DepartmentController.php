<?php

namespace App\Controller;

use App\Service\CallApiService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class DepartmentController extends AbstractController
{
    /**
     * @Route("/department/{department}", name="app_department")
     */
    public function index(string $department, CallApiService $callApiService, ChartBuilderInterface $chartBuilder): Response
    {
        $label = [];
        $hospitalisation = [];
        $rea = [];
 
        for ($i=1; $i < 8; $i++) { 
            $date = New DateTime('- '. $i .' day');
            $datas = $callApiService->getAllDataByDate($date->format('Y-m-d'));
            foreach ($datas as $data) {
                if( $data['lib_dep'] === $department) {
                    $label[] = $data['date'];
                    $hospitalisation[] = $data['incid_hosp'];
                    $rea[] = $data['incid_rea'];
                    break;
                }
            }
        }

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
            'data' => $callApiService->getDepartmentData($department),
            'chart' => $chart,
        ]);
    }
}
