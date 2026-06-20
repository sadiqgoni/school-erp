<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('staff')
            ->join('salary_templates', 'staff.salary_template_id', '=', 'salary_templates.id')
            ->whereNull('staff.salary_grade_level')
            ->update([
                'staff.salary_grade_level' => DB::raw('salary_templates.grade_level'),
                'staff.salary_step' => DB::raw('salary_templates.step'),
                'staff.basic_salary' => DB::raw('salary_templates.monthly_basic'),
            ]);
    }

    public function down(): void
    {
        //
    }
};
