<?php

namespace App\Observers;

use App\Models\UserActivity;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditModelObserver
{
    /**
     * @var array<int, string>
     */
    protected array $ignoredFields = [
        'created_at',
        'updated_at',
        'email_verified_at',
        'remember_token',
    ];

    /**
     * @var array<int, string>
     */
    protected array $importantFields = [
        'name',
        'email',
        'password',
        'is_active',
        'is_platform_admin',
        'status',
        'school_id',
        'student_id',
        'staff_id',
        'user_id',
        'school_class_id',
        'class_section_id',
        'academic_year_id',
        'term_id',
        'exam_id',
        'subject_id',
        'assessment_component_id',
        'score',
        'total_score',
        'average_score',
        'position',
        'teacher_comment',
        'principal_comment',
        'published_at',
        'amount',
        'paid_amount',
        'balance',
        'payment_status',
        'invoice_number',
        'payment_reference',
    ];

    public function created(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        AuditLogger::log(
            action: 'created',
            description: 'Created '.$this->modelName($model),
            auditable: $model,
            newValues: $this->cleanValues($model->getAttributes()),
        );
    }

    public function updated(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $changes = $this->cleanValues($model->getChanges());

        if ($changes === []) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($changes) as $field) {
            $oldValues[$field] = $model->getOriginal($field);
        }

        AuditLogger::log(
            action: 'updated',
            description: 'Updated '.$this->modelName($model),
            auditable: $model,
            oldValues: $oldValues,
            newValues: $changes,
        );
    }

    public function deleted(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        AuditLogger::log(
            action: 'deleted',
            description: 'Deleted '.$this->modelName($model),
            auditable: $model,
            oldValues: $this->cleanValues($model->getAttributes()),
        );
    }

    protected function shouldSkip(Model $model): bool
    {
        return $model instanceof UserActivity || app()->runningInConsole();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function cleanValues(array $values): array
    {
        foreach ($this->ignoredFields as $field) {
            unset($values[$field]);
        }

        return array_intersect_key($values, array_flip($this->importantFields));
    }

    protected function modelName(Model $model): string
    {
        return str(class_basename($model))->headline()->lower()->toString();
    }
}
