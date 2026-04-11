<?php

namespace Tests\Unit\Actions\Catalog;

use App\Actions\Catalog\LoadTitleBoxOfficeAction;
use App\Models\MovieBoxOffice;
use App\Models\Title;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class LoadTitleBoxOfficeActionTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();

        Schema::connection('imdb_mysql')->create('movie_box_office', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id')->primary();
            $table->decimal('domestic_gross_amount', 15, 2)->nullable();
            $table->string('domestic_gross_currency_code')->nullable();
            $table->decimal('worldwide_gross_amount', 15, 2)->nullable();
            $table->string('worldwide_gross_currency_code')->nullable();
            $table->decimal('opening_weekend_gross_amount', 15, 2)->nullable();
            $table->string('opening_weekend_gross_currency_code')->nullable();
            $table->unsignedSmallInteger('opening_weekend_end_year')->nullable();
            $table->unsignedTinyInteger('opening_weekend_end_month')->nullable();
            $table->unsignedTinyInteger('opening_weekend_end_day')->nullable();
            $table->decimal('production_budget_amount', 15, 2)->nullable();
            $table->string('production_budget_currency_code')->nullable();
        });
    }

    public function test_it_builds_real_reporting_cards_from_the_box_office_row(): void
    {
        $title = Title::query()->create([
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
        ]);

        MovieBoxOffice::query()->create([
            'movie_id' => $title->id,
            'domestic_gross_amount' => '171479930.00',
            'domestic_gross_currency_code' => 'USD',
            'worldwide_gross_amount' => '467222728.00',
            'worldwide_gross_currency_code' => 'USD',
            'opening_weekend_gross_amount' => '27788331.00',
            'opening_weekend_gross_currency_code' => 'USD',
            'opening_weekend_end_year' => 1999,
            'opening_weekend_end_month' => 4,
            'opening_weekend_end_day' => 4,
            'production_budget_amount' => '63000000.00',
            'production_budget_currency_code' => 'USD',
        ]);

        $payload = app(LoadTitleBoxOfficeAction::class)->handle($title);

        $this->assertSame(4, $payload['reportedFigureCount']);
        $this->assertSame('Lifetime Gross', $payload['spotlightMetric']['label']);
        $this->assertSame('USD 467,222,728', $payload['spotlightMetric']['value']);
        $this->assertSame(
            ['Lifetime gross reporting', 'Domestic gross reporting', 'Opening weekend reporting', 'Production budget reporting', 'Reported currencies', 'Figure coverage'],
            $payload['reportingRows']->pluck('label')->all(),
        );
        $this->assertSame(
            ['Lifetime Gross rank', 'Budget Multiple'],
            $payload['heroContextCards']->pluck('label')->all(),
        );
        $this->assertSame(['USD', 'USD', 'Apr 4, 1999', 'USD', 'USD', '4 / 4'], $payload['reportingRows']->pluck('badge')->all());
    }
}
