<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AppAnalyseMetricsCommand
 *
 * @package App\Command
 */
class AppAnalyseMetricsCommand extends Command
{
    const BYTES_TO_MEGABITS = 125000;

    /**
     * @var string
     */
    protected static $defaultName = 'app:analyse-metrics';

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this->setDescription('Analyses the metrics to generate a report.');
        $this->addOption('input', null, InputOption::VALUE_REQUIRED, 'The location of the test input');
    }

    /**
     * Detect slow-downs in the data and output them to stdout.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $filename = $input->getOption('input');
        $filedata = file_get_contents($filename);
        if (false === $filedata) {
            throw new \InvalidArgumentException(sprintf("Input file %s could not be read", $filename));
        }

        // Get relevant sub-data and exit if not found (missing array key)
        $data = json_decode($filedata, true)['data'][0]['metricData'] ?? null;
        if (is_null($data)) {
            throw new \InvalidArgumentException(sprintf('Bad data format'));
        }

        $dataLength = count($data);

        // sort data in ascending date order
        usort($data, function($item1, $item2) {
            return $item1['dtime'] > $item2['dtime'];
        });

        $from = $data[0]['dtime'];
        $to = $data[$dataLength - 1]['dtime'];

        // sort in ascending order
        usort($data, function($item1, $item2) {
            return $item1['metricValue'] > $item2['metricValue'];
        });

        // calculate min/max/average/median
        $min = round($data[0]['metricValue'] / self::BYTES_TO_MEGABITS, 2);
        $max = round($data[$dataLength - 1]['metricValue'] / self::BYTES_TO_MEGABITS, 2);

        $sum = $this->sumFloatValues($data);
        $average = round(($sum / $dataLength) / self::BYTES_TO_MEGABITS, 2);
        $median = $this->calculateMedian($data);

        $report = $this->getMainTemplate();
        $report = str_replace(
            ['{{from}}', '{{to}}', '{{average}}', '{{min}}', '{{max}}', '{{median}}'],
            [$from, $to, $average, $min, $max, $median],
            $report
        );

        // Find days with performance 10% less than average or worse
        $underperformingDays = array_filter($data, function($item) use ($average) {
            $megabitsPerSecond = round($item['metricValue'] / self::BYTES_TO_MEGABITS, 2);
            return $megabitsPerSecond < ($average * 0.9);
        });

        if (!empty($underperformingDays)) {
            // sort in ascending date order
            usort($underperformingDays, function($item1, $item2) {
                return $item1['dtime'] > $item2['dtime'];
            });

            $subReport = $this->getSubTemplate();
            $subReport = str_replace(
                ['{{from}}', '{{to}}'],
                [array_shift($underperformingDays)['dtime'], array_pop($underperformingDays)['dtime']],
                $subReport
            );

            $report .= PHP_EOL . PHP_EOL . $subReport . PHP_EOL;
        }

        $output->writeln($report);
    }

    /**
     * @param array $data
     * @return float
     */
    public function calculateMedian(array $data): float
    {
        $dataLength = count($data);
        usort($data, function($item1, $item2) {
            return $item1['metricValue'] > $item2['metricValue'];
        });

        // median is either single middle value or, if even list count, then mean average of 2 middle values
        if ($dataLength % 2 == 1) {
            $middleIndex = (int) floor($dataLength / 2);
            $median = round($data[$middleIndex]['metricValue'] / self::BYTES_TO_MEGABITS, 2);
        } else {
            $middleIndex = (int) floor($dataLength / 2) - 1;
            $averageOfMiddleValues = ($data[$middleIndex]['metricValue'] + $data[$middleIndex + 1]['metricValue']) / 2;
            $median = round($averageOfMiddleValues / self::BYTES_TO_MEGABITS, 2);
        }

        return $median;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function sumFloatValues(array $data)
    {
        $sum = array_reduce($data, function ($carry, $item) {
            $carry += $item['metricValue'];
            return $carry;
        }, 0);

        return $sum;
    }

    /**
     * @return string
     */
    private function getMainTemplate(): string
    {
        return <<<TEMPLATE
SamKnows Metric Analyser v1.0.0
===============================

Period checked:

    From: {{from}}
    To:   {{to}}

Statistics:

    Unit: Megabits per second

    Average: {{average}}
    Min: {{min}}
    Max: {{max}}
    Median: {{median}}
TEMPLATE;
    }

    private function getSubTemplate()
    {
        return <<<TEMPLATE
Investigate:

    * The period between {{from}} and {{to}}
      was under-performing.
TEMPLATE;
    }

}
