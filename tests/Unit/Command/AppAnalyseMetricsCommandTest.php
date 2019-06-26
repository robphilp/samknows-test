<?php declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\AppAnalyseMetricsCommand;

/**
 * Class AppAnalyseMetricsCommandTest
 *
 * @package App\Tests\Unit\Command
 */
class AppAnalyseMetricsCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array
     */
    public function sumFloatValuesProvider(): array
    {
        return [
            [
                [['metricValue' => 10.5], ['metricValue' => 15.3], ['metricValue' => 27.2]],
                53
            ],
            [
                [['metricValue' => 12.5], ['metricValue' => 0.25]],
                12.75
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sumFloatValuesProvider
     *
     * @param array $data
     * @param float $expected
     */
    public function testSumFloatValues(array $data, float $expected): void
    {
        $command = new AppAnalyseMetricsCommand();
        $this->assertEquals($expected, $command->sumFloatValues($data));
    }

    /**
     * @return array
     */
    public function calculateMedianProvider(): array
    {
        return [
            [
                [['metricValue' => 1250000], ['metricValue' => 2500000], ['metricValue' => 1500000]],
                12
            ],
            [
                [['metricValue' => 1250000], ['metricValue' => 2500000], ['metricValue' => 1500000], ['metricValue' => 1750000]],
                13
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calculateMedianProvider
     *
     * @param array $data
     * @param float $expected
     */
    public function testCalulateMedian(array $data, float $expected)
    {
        $command = new AppAnalyseMetricsCommand();
        $this->assertEquals($expected, $command->calculateMedian($data));
    }
}
