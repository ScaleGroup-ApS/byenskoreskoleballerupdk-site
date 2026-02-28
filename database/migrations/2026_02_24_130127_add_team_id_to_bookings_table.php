<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            DB::statement('DROP VIEW IF EXISTS admin_kpis_view');
            DB::statement('DROP VIEW IF EXISTS instructor_kpis_view');
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete()->after('student_id');
        });

        if ($isSqlite) {
            $this->recreateViews();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            DB::statement('DROP VIEW IF EXISTS admin_kpis_view');
            DB::statement('DROP VIEW IF EXISTS instructor_kpis_view');
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        if ($isSqlite) {
            $this->recreateViews();
        }
    }

    private function recreateViews(): void
    {
        $upcomingWindowEnd = "datetime('now', '+7 days')";
        $nowExpr = "datetime('now')";
        $greatestZero = 'CASE WHEN outstanding_raw < 0 THEN 0.0 ELSE outstanding_raw END';

        DB::statement('DROP VIEW IF EXISTS admin_kpis_view');
        DB::statement("CREATE VIEW admin_kpis_view AS
            SELECT
                (
                    SELECT COUNT(*)
                    FROM students
                    WHERE status = 'active'
                      AND deleted_at IS NULL
                ) AS total_students,

                (
                    SELECT COUNT(*)
                    FROM bookings
                    WHERE status = 'scheduled'
                      AND starts_at >= {$nowExpr}
                      AND starts_at <= {$upcomingWindowEnd}
                ) AS upcoming_bookings,

                (
                    SELECT ROUND(
                        IFNULL(
                            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) * 1.0 /
                            NULLIF(SUM(CASE WHEN status IN ('completed', 'no_show') THEN 1 ELSE 0 END), 0) * 100,
                            0
                        ), 1
                    )
                    FROM bookings
                ) AS no_show_rate,

                (
                    SELECT {$greatestZero} FROM (
                        SELECT
                            COALESCE((SELECT SUM(o.price) FROM offer_student os JOIN offers o ON o.id = os.offer_id), 0) -
                            COALESCE((SELECT SUM(amount) FROM payments), 0)
                            AS outstanding_raw
                    ) AS t
                ) AS total_outstanding
        ");

        DB::statement('DROP VIEW IF EXISTS instructor_kpis_view');
        DB::statement("CREATE VIEW instructor_kpis_view AS
            SELECT
                instructor_id,
                SUM(
                    CASE
                        WHEN status = 'scheduled'
                         AND starts_at >= {$nowExpr}
                         AND starts_at <= {$upcomingWindowEnd}
                        THEN 1 ELSE 0
                    END
                ) AS upcoming_bookings,
                ROUND(
                    IFNULL(
                        SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) * 1.0 /
                        NULLIF(SUM(CASE WHEN status IN ('completed', 'no_show') THEN 1 ELSE 0 END), 0) * 100,
                        0
                    ), 1
                ) AS no_show_rate
            FROM bookings
            GROUP BY instructor_id
        ");
    }
};
