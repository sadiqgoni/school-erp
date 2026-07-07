<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('salary_templates')
            ->orderBy('id')
            ->get(['id', 'grade_level', 'step', 'monthly_basic'])
            ->each(function (object $template): void {
                DB::table('staff')
                    ->where('salary_template_id', $template->id)
                    ->whereNull('salary_grade_level')
                    ->update([
                        'salary_grade_level' => $template->grade_level,
                        'salary_step' => $template->step,
                        'basic_salary' => $template->monthly_basic,
                    ]);
            });
    }

    public function down(): void
    {
        //
    }
};
