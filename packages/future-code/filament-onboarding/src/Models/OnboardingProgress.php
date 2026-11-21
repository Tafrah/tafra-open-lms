<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * تقدّم المستخدم في الأونبوردنج.
 *
 * الحالة في قاعدة البيانات مش في localStorage — عشان تنتقل بين
 * الأجهزة (docs/15 بند ٥ قاعدة ٤).
 */
final class OnboardingProgress extends Model
{
    protected $table = 'onboarding_progress';

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'tenant_id', 'completed_steps', 'completed_tours',
        'checklist_dismissed', 'wizard_completed_at',
    ];

    /**
     * مركزي دايماً — التقدّم ملك المستخدم داخل المؤسسة، والمستخدمين مركزيين.
     * الاتصال من الكونفيج عشان الباكدج مايعتمدش على stancl.
     */
    public function getConnectionName(): ?string
    {
        return config('fc-onboarding.connection') ?? parent::getConnectionName();
    }

    public function isDismissed(): bool
    {
        return (bool) $this->checklist_dismissed;
    }

    public function dismiss(): void
    {
        $this->forceFill(['checklist_dismissed' => true])->save();
    }

    public function hasCompletedTour(string $key): bool
    {
        return in_array($key, (array) $this->completed_tours, true);
    }

    public function markTourCompleted(string $key): void
    {
        $tours = array_values(array_unique([...(array) $this->completed_tours, $key]));

        $this->forceFill(['completed_tours' => $tours])->save();
    }

    public function markWizardCompleted(): void
    {
        $this->forceFill(['wizard_completed_at' => now()])->save();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'completed_steps' => 'array',
            'completed_tours' => 'array',
            'checklist_dismissed' => 'boolean',
            'wizard_completed_at' => 'datetime',
        ];
    }
}
