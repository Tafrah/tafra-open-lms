<?php

declare(strict_types=1);

namespace Src\Contexts\Demo\Application\Actions;

use Database\Seeders\StorefrontDemoSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Billing\Application\Actions\CheckoutAction;
use Src\Contexts\Billing\Application\Actions\EnrollStudentAction;
use Src\Contexts\Billing\Application\Actions\GenerateRechargeCardsAction;
use Src\Contexts\Billing\Application\Actions\ReviewInvoiceAction;
use Src\Contexts\Billing\Application\Actions\SubmitReceiptAction;
use Src\Contexts\Billing\Application\Actions\WalletAction;
use Src\Contexts\Billing\Application\DataObjects\RechargeCardBatchData;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Content\Domain\Enums\AnnouncementSeverity;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Demo\Application\DemoMode;
use Src\Contexts\Exams\Application\Actions\GradeEssayAnswerAction;
use Src\Contexts\Exams\Application\Actions\SaveAnswerAction;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Throwable;

/**
 * بيانات أكاديمية تجربة «شكلها شغّالة»: كورسات ودروس وامتحانات (StorefrontDemoSeeder)،
 * وفوقها طلبة بمحافظ واشتراكات، إيصالات مستنية المراجعة (منها واحد مكرر عشان
 * التحذير يبان)، محاولات امتحان منها مقالي مستني التصحيح، إعلانات، وكروت شحن.
 *
 * كله بيعدّي على نفس الـ Actions اللي اللوحة والموقع بيستخدموها — مش insert
 * مباشر — عشان الأرقام (المحافظ، الدرجات، التقارير) تبقى متسقة زي الحقيقة.
 *
 * بيتشغّل على قاعدة فاضية (`lms:demo` بعد التثبيت)؛ تشغيله مرتين بيزوّد محاولات وإيصالات.
 */
final readonly class SeedDemoAcademyAction
{
    /** @var list<array{0: string, 1: int}> الاسم والصف (0 أولى · 1 تانية · 2 تالتة) */
    private const STUDENTS = [
        ['يوسف محمود عبد الله', 2], ['مريم أحمد السيد', 2], ['عمر خالد حسن', 2], ['سلمى إبراهيم علي', 2],
        ['آدم مصطفى كمال', 2], ['نور هشام فؤاد', 2], ['زياد طارق سعيد', 2], ['جنى وليد عادل', 2],
        ['مالك أيمن رضا', 1], ['فرح شريف نبيل', 1], ['حمزة ياسر جمال', 1], ['ملك عمرو سامي', 1],
        ['سيف حسام الدين', 1], ['هنا كريم صلاح', 0], ['علي محمد فتحي', 0], ['رقية سامح عزت', 0],
        ['بلال أشرف منير', 0], ['جودي مازن رأفت', 0], ['أنس هاني لطفي', 2], ['لارا عصام ممدوح', 2],
    ];

    public function __construct(private DemoMode $demo) {}

    public function handle(): void
    {
        $tenant = Tenant::query()->orderBy('id')->firstOrFail();
        $context = app(TenantContext::class);
        $context->set((int) $tenant->getKey());

        $teacher = User::query()->where('email', $this->demo->account('teacher')['email'] ?? '')->firstOrFail();

        // auth()->user() بيحتاجه الـ activity log وبعض الـ observers وهو شغّال من الطرفية
        auth()->guard('web')->setUser($teacher);

        app(StorefrontDemoSeeder::class)->run();

        // كل الزوار بيدخلوا نفس حساب الطالب — حد الأجهزة كان هيقفل التاني
        app(TenantSettings::class)->set('site', 'max_devices_per_student', 0);

        $this->staff($tenant, $context);
        $this->demoStudent();

        $courses = Bootcamp::query()->orderBy('id')->get()->keyBy('slug');
        $students = $this->students();

        $this->wallets($students, $teacher);
        $this->purchases($students, $courses, $teacher);
        $this->attempts($courses, $teacher);
        $this->announcements($teacher);
        $this->cards($courses, $teacher);

        auth()->guard('web')->forgetUser();
        $context->set(null);
    }

    private function staff(Tenant $tenant, TenantContext $context): void
    {
        foreach ($this->demo->accounts('web') as $key => $account) {
            if ($key === 'teacher') {
                continue;
            }

            $user = User::query()->withTrashed()->where('email', $account['email'])->first() ?? new User;

            $user->forceFill([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => Hash::make($this->demo->password()),
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
                'is_active' => true,
                'locale' => 'ar',
                'deleted_at' => null,
            ])->save();

            $user->tenants()->syncWithoutDetaching([$tenant->getKey() => ['joined_at' => now()]]);

            $context->forTenant((int) $tenant->getKey(), static function () use ($user, $account): void {
                $user->unsetRelation('roles');
                $user->syncRoles([$account['role']]);
            });
        }

        $context->set((int) $tenant->getKey());
    }

    private function demoStudent(): void
    {
        $account = $this->demo->account('student') ?? [];

        Student::query()->where('phone', $account['phone'] ?? '01000000000')->update([
            'name' => $account['name'] ?? 'محمد أحمد علي',
            'password' => Hash::make($this->demo->password()),
        ]);
    }

    /** @return list<Student> */
    private function students(): array
    {
        $levels = EducationalLevel::query()->orderBy('order')->pluck('id')->all();
        $register = app(RegisterStudentAction::class);
        $students = [];

        foreach (self::STUDENTS as $i => [$name, $level]) {
            $student = $register->handle([
                'name' => $name,
                'phone' => '0'.(1010000001 + $i),
                'parent_phone' => '0'.(1120000000 + $i),
                'password' => $this->demo->password(),
                'educational_level_id' => (int) $levels[$level],
                'governorate' => ['cairo', 'giza', 'alexandria', 'dakahlia', 'sharqia'][$i % 5],
                'school_type' => null,
            ]);

            // تواريخ تسجيل على مدار الشهرين اللي فاتوا — الرسوم البيانية مش خط واحد
            $student->forceFill(['created_at' => now()->subDays(60 - $i * 3)->setTime(17 + $i % 5, ($i * 7) % 60)])->saveQuietly();

            $students[] = $student;
        }

        return $students;
    }

    /** @param list<Student> $students */
    private function wallets(array $students, User $teacher): void
    {
        $wallet = app(WalletAction::class);

        foreach ($students as $i => $student) {
            if ($i % 3 === 0) {
                $wallet->deposit($student, [150, 250, 400][intdiv($i, 3) % 3], TransactionSource::Admin, notes: 'إيداع في السنتر', actorId: (int) $teacher->getKey());
            }
        }
    }

    /**
     * @param list<Student> $students
     * @param Collection<string, Bootcamp> $courses
     */
    private function purchases(array $students, Collection $courses, User $teacher): void
    {
        $organic = $courses['organic-chemistry-sec3'];
        $electro = $courses['electrochemistry-sec3'];
        $free = $courses['intro-chemistry-free'];
        $matter = $courses['matter-structure-sec1'];

        $checkout = app(CheckoutAction::class);
        $enroll = app(EnrollStudentAction::class);
        $review = app(ReviewInvoiceAction::class);
        $method = PaymentMethod::query()->orderBy('order')->firstOrFail();
        $instapay = PaymentMethod::query()->orderBy('order')->skip(1)->first() ?? $method;

        // الطالب التجريبي: مشترك في العضوية (إيصال مقبول) والمجاني، وفاتورة الكهربية مستنية
        $demo = Student::query()->where('phone', $this->demo->account('student')['phone'] ?? '')->firstOrFail();
        $invoice = $checkout->handle($demo, $organic, PaymentGateway::ManualTransfer, 'WELCOME20');
        $this->receipt($demo, $invoice, $method, 'demo');
        $review->approve($invoice->refresh(), $teacher);
        $enroll->handle($demo, $free, EnrollmentSource::Free);

        foreach ($students as $i => $student) {
            $course = $i < 8 || $i >= 18 ? ($i % 2 === 0 ? $organic : $electro) : $matter;

            if ($i < 6) {
                // ٦ إيصالات اتقبلت
                $invoice = $checkout->handle($student, $course, PaymentGateway::ManualTransfer);
                $this->receipt($student, $invoice, $method, "ok-{$i}");
                $review->approve($invoice->refresh(), $teacher);
            } elseif ($i < 10) {
                // ٤ مستنية مراجعتك — الأخير بنفس صورة اللي قبله (تحذير «إيصال مكرر»)
                $invoice = $checkout->handle($student, $course, PaymentGateway::ManualTransfer);
                $this->receipt($student, $invoice, $instapay, $i === 9 ? 'pending-8' : "pending-{$i}", sender: '0'.(1550000000 + $i));
            } elseif ($i === 10) {
                // واحد اترفض بسبب
                $invoice = $checkout->handle($student, $course, PaymentGateway::ManualTransfer);
                $this->receipt($student, $invoice, $method, 'rejected');
                $review->reject($invoice->refresh(), $teacher, 'المبلغ المحوّل أقل من سعر الكورس — حوّل الباقي وارفع الإيصال تاني.');
            } else {
                // اشتراكات من السنتر (تفعيل يدوي) والمجاني
                $enroll->handle($student, $course, EnrollmentSource::Admin, enrolledBy: (int) $teacher->getKey());

                if ($i % 2 === 0) {
                    $enroll->handle($student, $free, EnrollmentSource::Free);
                }
            }
        }
    }

    /** @param Collection<string, Bootcamp> $courses */
    private function attempts(Collection $courses, User $teacher): void
    {
        $start = app(StartAttemptAction::class);
        $save = app(SaveAnswerAction::class);
        $submit = app(SubmitAttemptAction::class);
        $grade = app(GradeEssayAnswerAction::class);

        foreach (['organic-chemistry-sec3', 'electrochemistry-sec3', 'matter-structure-sec1'] as $slug) {
            $course = $courses[$slug];
            $item = BootcampSectionItem::query()
                ->whereIn('bootcamp_section_id', $course->sections()->pluck('id'))
                ->where('type', SectionItemType::Exam)
                ->first();

            if ($item === null || $item->online_exam_id === null) {
                continue;
            }

            $exam = $item->exam()->firstOrFail();
            $questions = Question::query()->where('online_exam_id', $exam->getKey())->with('choices')->orderBy('order')->get();
            $enrolled = Student::query()
                ->whereHas('enrollments', fn ($query) => $query->where('bootcamp_id', $course->getKey()))
                ->orderBy('id')
                ->get();

            foreach ($enrolled as $n => $student) {
                try {
                    $attempt = $start->handle($student, $exam, (int) $item->getKey());

                    foreach ($questions as $q => $question) {
                        if ($question->type === QuestionType::Essay) {
                            $save->handle($student, $attempt, (int) $question->getKey(), text: 'الرابطة الأيونية بتتكوّن بانتقال إلكترونات من فلز للافلز زي NaCl، والتساهمية بالمشاركة بين لافلزين زي H₂O.');

                            continue;
                        }

                        // درجات متنوّعة: كل طالب بيغلط في سؤال مختلف، وكام واحد بيجيب الكل
                        $correct = ($n + $q) % 4 !== 0 || $n % 5 === 0;
                        $choice = $question->choices->first(fn ($c): bool => (bool) $c->is_correct === $correct) ?? $question->choices->first();
                        $save->handle($student, $attempt, (int) $question->getKey(), choiceId: $choice !== null ? (int) $choice->getKey() : null);
                    }

                    $submit->handle($attempt);

                    // نص المقالي اتصحّح، والنص التاني مستني في «تصحيح المقالي»
                    if ($n % 2 === 0) {
                        $essay = StudentExamAnswer::query()
                            ->where('online_passed_exam_id', $attempt->getKey())
                            ->whereHas('question', fn ($query) => $query->where('type', QuestionType::Essay))
                            ->first();

                        if ($essay !== null) {
                            $grade->handle($essay, 3, 'إجابة كويسة — ناقصها مثال على الرابطة التناسقية.', $teacher);
                        }
                    }
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }
    }

    private function announcements(User $teacher): void
    {
        foreach ([
            ['مراجعة ليلة الامتحان', '<p>المراجعة النهائية على الكيمياء العضوية يوم <strong>الخميس الساعة ٨ مساءً</strong> لايف على المنصة. حضّر أسئلتك!</p>', AnnouncementSeverity::Urgent, true, 1],
            ['نزل امتحان الباب التاني', '<p>امتحان الكيمياء الكهربية متاح دلوقتي في صفحة الكورس — عندك ٣ محاولات.</p>', AnnouncementSeverity::Info, false, 3],
            ['مواعيد السنتر الجديدة', '<p>من الأسبوع الجاي: السبت والثلاثاء ٤ العصر، والمجموعة التانية ٦.</p>', AnnouncementSeverity::Warning, false, 7],
        ] as [$title, $body, $severity, $pinned, $daysAgo]) {
            Announcement::query()->create([
                'author_id' => $teacher->getKey(),
                'title' => ['ar' => $title, 'en' => $title],
                'body' => ['ar' => $body, 'en' => $body],
                'status' => AnnouncementStatus::Published,
                'severity' => $severity,
                'is_pinned' => $pinned,
                'published_at' => now()->subDays($daysAgo),
            ]);
        }
    }

    /** @param Collection<string, Bootcamp> $courses */
    private function cards(Collection $courses, User $teacher): void
    {
        $generate = app(GenerateRechargeCardsAction::class);

        $generate->handle(new RechargeCardBatchData('كروت السنتر — ١٠٠ جنيه', RechargeCardType::Wallet, 30, 100.0), $teacher);
        $generate->handle(new RechargeCardBatchData('كروت كورس العضوية', RechargeCardType::Bootcamp, 15, 0.0, (int) $courses['organic-chemistry-sec3']->getKey()), $teacher);
    }

    /**
     * صورة إيصال PNG صغيرة. نفس `$seed` = نفس البايتات = نفس البصمة — كده
     * المراجع بيشوف تحذير «الإيصال ده اتبعت قبل كده» على الفاتورة المكررة.
     */
    private function receipt(Student $student, PaymentInvoice $invoice, PaymentMethod $method, string $seed, ?string $sender = null): void
    {
        $path = tempnam(sys_get_temp_dir(), 'demo-receipt');
        file_put_contents($path, self::png().'DEMO:'.$seed);

        app(SubmitReceiptAction::class)->handle(
            $student,
            $invoice,
            $method,
            new UploadedFile($path, 'receipt.png', 'image/png', null, true),
            senderPhone: $sender ?? (string) $student->phone,
            reference: 'TX'.strtoupper(substr(sha1($seed), 0, 8)),
        );
    }

    /** PNG ١×١ (البيانات بعد IEND بتتجاهل وقت العرض) */
    private static function png(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
    }
}
