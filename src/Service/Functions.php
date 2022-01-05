<?php
 
namespace App\Service;

use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class Functions {

    public $chart;

    public function __construct(ChartBuilderInterface $chartBuilder)
    {
        $this->chart = $chartBuilder;
    }

    public function chartLine(string $type ,array $label, array $data1, array $data2)
    {
        $chart = $this->chart->createChart($type);
        $chart->setData([
            'labels' => $label,
            'borderWidth' => 1,
            'datasets' => [
                [
                    'label' => 'Nouvelles Hospitalisations',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $data1,
                ],
                [
                    'label' => 'Nouvelles entrées en Réa',
                    'borderColor' => 'rgb(46, 41, 78)',
                    'data' => $data2,
                ],
            ],
        ]);

        $chart->setOptions([/* ... */]); 

        return $chart;
    }

    public function chartBar(string $type ,array $label, array $data1, array $data2)
    {
        $chart = $this->chart->createChart($type);
        $chart->setData([
            'labels' => $label,
            'datasets' => [
                [
                    'label' => 'Nombre d’hospitalisations',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'data' => $data1,
                ],
                [
                    'label' => 'Nombre de Réa',
                    'backgroundColor' => 'rgb(46, 41, 78)',
                    'data' => $data2,
                ],
            ],
        ]);

        $chart->setOptions([
                      'indexAxis' => 'y'
                ]); 

        return $chart;
    }
}