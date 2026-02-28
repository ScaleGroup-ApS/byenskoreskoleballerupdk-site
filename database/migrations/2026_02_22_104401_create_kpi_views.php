<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['status', 'starts_at'], 'bookings_status_starts_at_index');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->index(['status', 'deleted_at'], 'students_status_deleted_at_index');
        });

        $isSqlite = DB::getDriverName() === 'sqlite';

        $upcomingWindowEnd = $isSqlite
            ? "datetime('now', '+7 days')"
            : 'DATE_ADD(NOW(), INTERVAL 7 DAY)';

        $nowExpr = $isSqlite ? "datetime('now')" : 'NOW()';

        $greatestZero = $isSqlite
            ? 'CASE WHEN outstanding_raw < 0 THEN 0.0 ELSE outstanding_raw END'
            : 'GREATEST(outstanding_raw, 0)';

        $createOrReplace = $isSqlite ? 'CREATE' : 'CREATE OR REPLACE';

        DB::statement('DROP VIEW IF EXISTS admin_kpis_view');
        DB::statement("{$createOrReplace} VIEW admin_kpis_view AS
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
        DB::statement("{$createOrReplace} VIEW instructor_kpis_view AS
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

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS instructor_kpis_view');
        DB::statement('DROP VIEW IF EXISTS admin_kpis_view');

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_status_deleted_at_index');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_status_starts_at_index');
        });
    }
};
