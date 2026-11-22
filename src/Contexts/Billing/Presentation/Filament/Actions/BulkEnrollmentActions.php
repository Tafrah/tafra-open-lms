<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\BulkEnrollmentAction;
use Src\Contexts\Billing\Application\DataObjects\BulkEnrollmentResult;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * زراير شاشة الاشتراكات: تفعيل/إلغاء بأرقام الموبايل، واشتراك طالب واحد.
 * الصلاحية `create.enrollments` (المساعد عنده)، والـ Gate تاني جوه الأكشن.
 */
final class BulkEnrollmentActions
{
    private const PREFIX = 'billing::billing.enrollment.bulk';

    public static function activate(): Action
    {
        return Action::make('activateByPhones')
            ->label(__(self::PREFIX.'.activate'))
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->model(Enrollment::class)
            ->authorize('create')
            ->modalDescription(__(self::PREFIX.'.activate_help'))
            ->schema([
                self::courseField(),
                self::phonesField(),
                self::chargeField(),
            ])
            ->modalSubmitActionLabel(__(self::PREFIX.'.activate_submit'))
            ->action(function (array $data): void {
                self::run(fn (BulkEnrollmentAction $bulk): BulkEnrollmentResult => $bulk->activate(
                    self::course($data),
                    (string) $data['phones'],
                    (bool) ($data['charge_wallet'] ?? false),
                    self::actor(),
                ), cancel: false);
            });
    }

    public static function cancel(): Action
    {
        return Action::make('cancelByPhones')
            ->label(__(self::PREFIX.'.cancel'))
            ->icon('heroicon-o-user-minus')
            ->color('danger')
            ->model(Enrollment::class)
            ->authorize('create')
            ->modalDescription(__(self::PREFIX.'.cancel_help'))
            ->schema([
                self::courseField(),
                self::phonesField(),
            ])
            ->modalSubmitActionLabel(__(self::PREFIX.'.cancel_submit'))
            ->action(function (array $data): void {
                self::run(fn (BulkEnrollmentAction $bulk): BulkEnrollmentResult => $bulk->cancel(
                    self::course($data),
                    (string) $data['phones'],
                    self::actor(),
                ), cancel: true);
            });
    }

    /** اشتراك طالب واحد — نفس الأكشن برقمه، فالقواعد واحدة */
    public static function enrollOne(): Action
    {
        return Action::make('enrollStudent')
            ->label(__(self::PREFIX.'.enroll_one'))
            ->icon('heroicon-o-academic-cap')
            ->color('gray')
            ->model(Enrollment::class)
            ->authorize('create')
            ->schema([
                Select::make('student_id')
                    ->label(__('billing::billing.enrollment.fields.student'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Student::query()
                        ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->limit(20)
                        ->get(['id', 'name', 'phone'])
                        ->mapWithKeys(fn (Student $s): array => [$s->getKey() => "{$s->name} — {$s->phone}"])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => Student::query()->find($value)?->name)
                    ->required(),
                self::courseField(),
                self::chargeField(),
            ])
            ->action(function (array $data): void {
                $student = Student::query()->findOrFail((int) $data['student_id']);

                self::run(fn (BulkEnrollmentAction $bulk): BulkEnrollmentResult => $bulk->activate(
                    self::course($data),
                    (string) $student->phone,
                    (bool) ($data['charge_wallet'] ?? false),
                    self::actor(),
                ), cancel: false);
            });
    }

    /** @param callable(BulkEnrollmentAction): BulkEnrollmentResult $operation */
    private static function run(callable $operation, bool $cancel): void
    {
        try {
            $result = $operation(app(BulkEnrollmentAction::class));
        } catch (BillingException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        $notification = Notification::make()
            ->title(__(self::PREFIX.($cancel ? '.cancel_done' : '.activate_done'), ['count' => fc_number(count($result->done))]))
            ->body(self::summary($result, $cancel))
            ->persistent();

        $clean = $result->notFound === [] && $result->insufficientBalance === [] && $result->failed === [];
        ($clean ? $notification->success() : $notification->warning())->send();
    }

    public static function summary(BulkEnrollmentResult $result, bool $cancel): string
    {
        $lines = [];
        $buckets = [
            'done' => $result->done,
            $cancel ? 'not_enrolled' : 'already_enrolled' => $result->skipped,
            'not_found' => $result->notFound,
            'insufficient_balance' => $result->insufficientBalance,
        ];

        foreach ($buckets as $key => $phones) {
            if ($phones === []) {
                continue;
            }

            $label = $key === 'done' ? ($cancel ? 'canceled' : 'activated') : $key;
            $lines[] = __(self::PREFIX.".result.{$label}", ['count' => fc_number(count($phones))]).': '.implode('، ', $phones);
        }

        foreach ($result->failed as $phone => $reason) {
            $lines[] = __(self::PREFIX.'.result.failed', ['phone' => $phone, 'reason' => $reason]);
        }

        // الـ body بيتعرض HTML (بعد sanitize) — الأرقام والأسباب بتتهرّب الأول
        return $lines === [] ? __(self::PREFIX.'.result.empty') : nl2br(e(implode("\n", $lines)));
    }

    private static function courseField(): Select
    {
        return Select::make('bootcamp_id')
            ->label(__('billing::billing.enrollment.fields.bootcamp'))
            ->options(fn (): array => Bootcamp::query()->orderBy('name')->pluck('name', 'id')->all())
            ->searchable()
            ->required();
    }

    private static function phonesField(): Textarea
    {
        return Textarea::make('phones')
            ->label(__(self::PREFIX.'.phones'))
            ->helperText(__(self::PREFIX.'.phones_help', ['max' => fc_number(BulkEnrollmentAction::MAX_PHONES)]))
            ->rows(8)
            ->required();
    }

    private static function chargeField(): Toggle
    {
        return Toggle::make('charge_wallet')
            ->label(__(self::PREFIX.'.charge_wallet'))
            ->helperText(__(self::PREFIX.'.charge_wallet_help'))
            ->default(false);
    }

    /** @param array<string, mixed> $data */
    private static function course(array $data): Bootcamp
    {
        return Bootcamp::query()->findOrFail((int) $data['bootcamp_id']);
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthorizationException::class);

        return $user;
    }
}
