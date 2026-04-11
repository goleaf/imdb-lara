<?php

use App\Enums\ContributionStatus;
use App\Enums\ReportStatus;
use App\Models\Contribution;
use App\Models\Report;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->unsignedTinyInteger('status_priority')
                ->default(ReportStatus::Open->priority())
                ->after('status')
                ->index();
        });

        Report::query()->where('status', ReportStatus::Investigating->value)->update([
            'status_priority' => ReportStatus::Investigating->priority(),
        ]);
        Report::query()->where('status', ReportStatus::Resolved->value)->update([
            'status_priority' => ReportStatus::Resolved->priority(),
        ]);
        Report::query()->where('status', ReportStatus::Dismissed->value)->update([
            'status_priority' => ReportStatus::Dismissed->priority(),
        ]);
        Report::query()->whereNull('status')->update([
            'status_priority' => ReportStatus::Open->priority(),
        ]);

        Schema::table('contributions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('status_priority')
                ->default(ContributionStatus::Submitted->priority())
                ->after('status')
                ->index();
        });

        Contribution::query()->where('status', ContributionStatus::Approved->value)->update([
            'status_priority' => ContributionStatus::Approved->priority(),
        ]);
        Contribution::query()->where('status', ContributionStatus::Rejected->value)->update([
            'status_priority' => ContributionStatus::Rejected->priority(),
        ]);
        Contribution::query()->whereNull('status')->update([
            'status_priority' => ContributionStatus::Submitted->priority(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contributions', function (Blueprint $table): void {
            $table->dropIndex(['status_priority']);
            $table->dropColumn('status_priority');
        });

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropIndex(['status_priority']);
            $table->dropColumn('status_priority');
        });
    }
};
