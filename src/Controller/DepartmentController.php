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

class DepartmentController extends AbstractController
{
    /**
     * @Route("/department/{department}", name="app_department")
     */
    public function index(string $department, CallApiService $callApiService, ChartService $chart, CacheInterface $cache): Response
    {
        $data_chart =$cache->get('result_department_detail_7j' . $department, function(ItemInterface $item) use($callApiService, $department){
                        $item->expiresAt(new \DateTime('tomorrow')); 
                        $datas = [];
                        for ($i=1; $i < 8; $i++) {
                            $day = date('d-m-Y', strtotime('-'. $i . ' days'));
                            $datas[] = $callApiService->getDepartmentDataByDate($department, $day);
                        };
                        return $datas;
        });

        $datas = [];
        foreach ($data_chart as $data) {
            $datas[] = $data[0];
        };

        $data_dep = current($datas);

        $label = [];
        $hospitalisation = [];
        $rea = [];

        foreach ($datas as $data) {
            $label[] = $data['date'];
            $hospitalisation[] = $data['incid_hosp'];
            $rea[] = $data['incid_rea'];
            }; 

        $label = array_reverse($label);
        $hospitalisation = array_reverse($hospitalisation);
        $rea = array_reverse($rea);
        $chart = $chart->chartLine(Chart::TYPE_LINE, $label, $hospitalisation, $rea);
       
        return $this->render('department/index.html.twig', [
            'data_dep' => $data_dep,
            'chart' => $chart,
        ]);
    }
}
