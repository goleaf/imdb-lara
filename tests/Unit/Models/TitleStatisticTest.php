<?php

namespace Tests\Unit\Models;

use App\Models\TitleStatistic;
use PHPUnit\Framework\TestCase;

class TitleStatisticTest extends TestCase
{
    public function test_it_normalizes_rating_distribution_payloads(): void
    {
        $distribution = TitleStatistic::normalizeRatingDistribution([
            '10' => 4,
            8 => '2',
            '5' => -3,
            '1' => 1,
            '12' => 9,
        ]);

        $this->assertSame([
            '10' => 4,
            '9' => 0,
            '8' => 2,
            '7' => 0,
            '6' => 0,
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 1,
        ], $distribution);
    }

    public function test_model_returns_a_normalized_distribution_from_its_attribute(): void
    {
        $statistic = new TitleStatistic([
            'rating_distribution' => [
                '9' => 3,
                7 => 2,
                '4' => 1,
            ],
        ]);

        $this->assertSame([
            '10' => 0,
            '9' => 3,
            '8' => 0,
            '7' => 2,
            '6' => 0,
            '5' => 0,
            '4' => 1,
            '3' => 0,
            '2' => 0,
            '1' => 0,
        ], $statistic->normalizedRatingDistribution());
    }
}
