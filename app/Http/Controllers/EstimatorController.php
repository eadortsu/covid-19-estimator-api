<?php

namespace App\Http\Controllers;

use App\Log;
use Illuminate\Http\Request;

class EstimatorController extends Controller
{
    //

    public function index(Request $request)
    {

        $log = Log::create([
            'method' => $_SERVER["REQUEST_METHOD"],
            'path' => '/api/v1/on-covid-19/json',
            'status' => '200',
            'respond_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) * 1000,
        ]);
        return $this->covid19ImpactEstimator($request);

    }

    private function covid19ImpactEstimator($data)
    {
        $data = $data->all();

        if (is_array($data)) {
            $input = $this->array_to_object($data);
        } elseif (is_string($data)) {
            $input = json_decode($data);
        } else {
            $input = $data;
        }


        $impact = new Log();
        $severImpact = new Log();
        $output = new Log();


        // Calculate impact.currentlyInfected
        $impact->currentlyInfected = $input->reportedCases * 10;

        // Calculate severImpact.currentlyInfected
        $severImpact->currentlyInfected = $input->reportedCases * 50;

        // Calculate infectionsByRequestedTime
        $duration = $this->normaliseDuration($input->periodType, $input->timeToElapse);
        $factor = intval(($duration / 3));
        $impact->infectionsByRequestedTime = intval($impact->currentlyInfected * (pow(2, $factor)));
        $severImpact->infectionsByRequestedTime = intval($severImpact->currentlyInfected * (pow(2, $factor)));

        // Challenge 2
        // Calculate severeCasesByRequestedTime
        $impact->severeCasesByRequestedTime = intval($impact->infectionsByRequestedTime * (15 / 100));
        $severImpact->severeCasesByRequestedTime = intval($severImpact->infectionsByRequestedTime * (15 / 100));

        // Calculate hospitalBedsByRequestedTime
        $availableBed = $input->totalHospitalBeds * (35 / 100);
        $impact->hospitalBedsByRequestedTime = intval($availableBed - $impact->severeCasesByRequestedTime);
        $severImpact->hospitalBedsByRequestedTime = intval($availableBed - $severImpact->severeCasesByRequestedTime);

        // Challenge 3
        $impact->casesForICUByRequestedTime = intval($impact->infectionsByRequestedTime * (5 / 100));
        $severImpact->casesForICUByRequestedTime = intval($severImpact->infectionsByRequestedTime * (5 / 100));

        //  casesForVentilatorsByRequestedTime
        $impact->casesForVentilatorsByRequestedTime = intval($impact->infectionsByRequestedTime * (2 / 100));
        $severImpact->casesForVentilatorsByRequestedTime = intval($severImpact->infectionsByRequestedTime * (2 / 100));

        // dollarsInFlight
        $impact->dollarsInFlight = intval(($impact->infectionsByRequestedTime * $input->region->avgDailyIncomePopulation * $input->region->avgDailyIncomeInUSD) / $duration);
        $severImpact->dollarsInFlight = intval(($severImpact->infectionsByRequestedTime * $input->region->avgDailyIncomePopulation * $input->region->avgDailyIncomeInUSD) / $duration);

        $output->data = $input; // the input data you got
        $output->impact = $impact; // your best case estimation
        $output->severeImpact = $severImpact; // your severe case estimation
        return $output;
    }

    private function array_to_object($array)
    {
        $obj = new Log();
        foreach ($array as $k => $v) {
            if (strlen($k)) {
                if (is_array($v)) {
                    $obj->{$k} = $this->array_to_object($v); //RECURSION
                } else {
                    $obj->{$k} = $v;
                }
            }
        }
        return $obj;
    }

    private function normaliseDuration($periodType, $timeToElapse)
    {
        $days = 0;

        switch ($periodType) {
            case "days":
                $days = $timeToElapse;
                break;
            case "weeks":
                $days = 7 * $timeToElapse;
                break;
            case "months":
                $days = 30 * $timeToElapse;
                break;
            case "years":
                $days = 365 * $timeToElapse;
                break;
            default:
                break;
        }

        return $days;
    }

    public function json(Request $request)
    {

        $log = Log::create([
            'method' => $_SERVER["REQUEST_METHOD"],
            'path' => '/api/v1/on-covid-19/json',
            'status' => '200',
            'respond_time' => round(microtime(true) - $_SERVER['REQUEST_TIME'], 3) * 1000
        ]);
        return $this->covid19ImpactEstimator($request);

    }

    public function xml(Request $request)
    {


        $log = Log::create([
            'method' => $_SERVER["REQUEST_METHOD"],
            'path' => '/api/v1/on-covid-19/json',
            'status' => '200',
            'respond_time' => round(microtime(true) - $_SERVER['REQUEST_TIME'], 3) * 1000
        ]);
        $data = $this->covid19ImpactEstimator($request);

        return response($data, 200)
            ->header('Content-Type', 'text/xml');

    }

    public function logs()
    {
        $logs = Log::all();
        $log = Log::create([
            'method' => $_SERVER["REQUEST_METHOD"],
            'path' => '/api/v1/on-covid-19/logs',
            'status' => '200',
            'respond_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) * 1000,
        ]);

        $contents = "";

        foreach ($logs as $log) {
            $contents .= $log->method . " " . $log->path . " " . $log->status . " " . $log->respond_time . "ms\n";
        }

        return response($contents, 200)
            ->header('Content-Type', 'text/plain');

    }

    private function object_to_array($array)
    {
        $obj = [];
        foreach ($array as $k => $v) {
            if (strlen($k)) {
                if (is_object($v)) {
                    $obj[$k] = object_to_array($v); //RECURSION
                } else {
                    $obj[$k] = $v;
                }
            }
        }
        return $obj;
    }
}
