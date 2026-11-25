<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Billing\Application\Actions\WalletAction;
use Src\Contexts\Billing\Domain\Enums\PaymentMethodType;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Students\Domain\Enums\SchoolType;
use Src\Contexts\Students\Domain\Enums\StudentStatus;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/**
 * بيانات تجربة للموقع العام: ٣ صفوف، ٤ كورسات بأجزاء ودروس (يوتيوب + نص)
 * وامتحان (اختيار من متعدد + مقالي)، طريقتين دفع، وطالب تجربة.
 *
 *   php artisan db:seed --class=StorefrontDemoSeeder
 *
 * الطالب: 01000000000 / password — رصيد ٣٠٠ جنيه، وكود شحن 111122223333 (١٠٠ جنيه).
 * بيتشغّل أكتر من مرة من غير تكرار (updateOrCreate على مفاتيح ثابتة).
 */
final class StorefrontDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->active()->orderBy('id')->first()
            ?? Tenant::query()->create([
                'slug' => 'demo-academy',
                'name' => ['ar' => 'أكاديمية التجربة', 'en' => 'Demo Academy'],
                'description' => ['ar' => '', 'en' => ''],
                'is_active' => true,
            ]);

        app(TenantContext::class)->set((int) $tenant->getKey());

        $this->siteSettings();
        $levels = $this->levels();
        $this->paymentMethods();
        $courses = $this->courses($levels);
        $this->student($levels[2], $courses);
    }

    private function siteSettings(): void
    {
        $settings = app(TenantSettings::class);

        foreach ([
            'platform_name' => 'منصة الكيمياء',
            'teacher_name' => 'أ. أحمد المصري',
            'subject' => 'كيمياء',
            'hero_description' => 'شرح الكيمياء لثانوي عام وأزهر من الصفر للدرجة النهائية — فيديوهات، مذكرات، وامتحانات بعد كل درس.',
            'hero_chip_1' => 'شرح مبسّط بالأمثلة',
            'hero_chip_2' => 'امتحان بعد كل درس',
            'hero_chip_3' => 'متابعة مع ولي الأمر',
            'about_text' => "مدرس كيمياء من ١٥ سنة.\nبيشرح المنهج كله بطريقة سهلة، ومعاك لحد يوم الامتحان.",
            'show_stats' => true,
            'facebook_url' => 'https://facebook.com/',
            'youtube_url' => 'https://youtube.com/',
            'whatsapp_number' => '01000000001',
            'floating_whatsapp' => true,
            'primary_color' => '#2563EB',
            'secondary_color' => '#7C3AED',
            'font_family' => 'Almarai',
        ] as $key => $value) {
            $settings->set('site', $key, $value);
        }
    }

    /** @return list<EducationalLevel> */
    private function levels(): array
    {
        $levels = [];

        foreach ([
            ['code' => 'SEC1', 'name' => 'الصف الأول الثانوي', 'color' => '#0EA5E9', 'order' => 1],
            ['code' => 'SEC2', 'name' => 'الصف الثاني الثانوي', 'color' => '#8B5CF6', 'order' => 2],
            ['code' => 'SEC3', 'name' => 'الصف الثالث الثانوي', 'color' => '#F97316', 'order' => 3],
        ] as $level) {
            $levels[] = EducationalLevel::query()->updateOrCreate(['code' => $level['code']], $level);
        }

        return $levels;
    }

    private function paymentMethods(): void
    {
        PaymentMethod::query()->updateOrCreate(['account_number' => '01000000002'], [
            'name' => 'فودافون كاش',
            'type' => PaymentMethodType::VodafoneCash,
            'account_name' => 'أحمد المصري',
            'instructions' => "حوّل المبلغ بالظبط واعمل سكرين شوت للرسالة.\nاكتب رقمك اللي حوّلت منه في الفورم.",
            'is_active' => true,
            'order' => 1,
        ]);

        PaymentMethod::query()->updateOrCreate(['account_number' => 'ahmed.chem@instapay'], [
            'name' => 'إنستاباي',
            'type' => PaymentMethodType::Instapay,
            'account_name' => 'Ahmed Elmasry',
            'instructions' => 'التحويل على العنوان ده، وارفع صورة الإيصال من التطبيق.',
            'is_active' => true,
            'order' => 2,
        ]);

        Coupon::query()->updateOrCreate(['code' => 'WELCOME20'], [
            'name' => 'خصم الترحيب',
            'type' => 'percentage',
            'value' => 20,
            'max_per_user' => 1,
            'applicable_type' => 'all',
            'is_active' => true,
        ]);
    }

    /**
     * @param list<EducationalLevel> $levels
     * @return list<Bootcamp>
     */
    private function courses(array $levels): array
    {
        $catalog = [
            ['slug' => 'organic-chemistry-sec3', 'name' => 'الكيمياء العضوية — تالتة ثانوي', 'level' => 2, 'price' => 250, 'discount' => 199, 'featured' => true],
            ['slug' => 'electrochemistry-sec3', 'name' => 'الكيمياء الكهربية — تالتة ثانوي', 'level' => 2, 'price' => 200, 'discount' => null, 'featured' => true],
            ['slug' => 'matter-structure-sec1', 'name' => 'تركيب المادة — أولى ثانوي', 'level' => 0, 'price' => 150, 'discount' => null, 'featured' => false],
            ['slug' => 'intro-chemistry-free', 'name' => 'مدخل الكيمياء — مجاني', 'level' => 1, 'price' => 0, 'discount' => null, 'featured' => true],
        ];

        // أفلام Blender المفتوحة (CC-BY) — متاحة للتضمين، ومحايدة لحد ما المدرس يحط فيديوهاته
        $videos = ['aqz-KE-bpKQ', 'eRsGyueVLvQ', 'R6MlUcmOul8', 'ScMzIvxBSi4'];
        $courses = [];

        foreach ($catalog as $index => $data) {
            $course = Bootcamp::query()->updateOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'educational_level_id' => $levels[$data['level']]->getKey(),
                'short_description' => 'شرح كامل للباب بالأمثلة والتمارين، وامتحان في آخر كل جزء.',
                'description' => '<h2>هتتعلم إيه؟</h2><ul><li>الشرح النظري خطوة بخطوة</li><li>حل مسائل الامتحانات</li><li>مراجعة نهائية</li></ul><p>مناسب لطلاب العام والأزهر.</p>',
                'promotional_video_url' => 'https://www.youtube.com/watch?v='.$videos[$index],
                'publish_status' => PublishStatus::Published,
                'price' => $data['price'],
                'price_currency' => 'EGP',
                'discount_price' => $data['discount'],
                'discount_start_date' => $data['discount'] !== null ? now()->subDay() : null,
                'discount_end_date' => $data['discount'] !== null ? now()->addWeeks(2) : null,
                'is_featured' => $data['featured'],
                'featured_order' => $data['featured'] ? $index + 1 : null,
            ]);

            $this->content($course, $index, $videos);
            $courses[] = $course;
        }

        return $courses;
    }

    /** @param list<string> $videos */
    private function content(Bootcamp $course, int $index, array $videos): void
    {
        if ($course->sections()->exists()) {
            return;
        }

        foreach (['الجزء الأول: الأساسيات', 'الجزء الثاني: التطبيقات'] as $order => $sectionName) {
            $section = BootcampSection::query()->create([
                'bootcamp_id' => $course->getKey(),
                'name' => $sectionName,
                'order' => $order + 1,
                'publish_status' => PublishStatus::Published,
            ]);

            $video = OnlineLesson::query()->create([
                'name' => "فيديو الشرح — {$sectionName}",
                'type' => LessonType::YoutubeVideo,
                'external_url' => 'https://youtu.be/'.$videos[($index + $order) % count($videos)],
                'duration_in_seconds' => 1500,
                'description' => 'شوف الفيديو للآخر وحل التمارين اللي في المذكرة.',
            ]);

            $text = OnlineLesson::query()->create([
                'name' => "ملخّص مكتوب — {$sectionName}",
                'type' => LessonType::Text,
                'text' => '<h3>أهم النقاط</h3><p>المادة بتتكوّن من <strong>ذرات</strong>، والذرة فيها بروتونات ونيوترونات وإلكترونات.</p><ul><li>العدد الذري = عدد البروتونات</li><li>العدد الكتلي = البروتونات + النيوترونات</li></ul>',
                'duration_in_seconds' => 300,
            ]);

            BootcampSectionItem::query()->create([
                'bootcamp_section_id' => $section->getKey(), 'name' => $video->name, 'order' => 1,
                'type' => SectionItemType::Lesson, 'online_lesson_id' => $video->getKey(),
                'publish_status' => PublishStatus::Published, 'is_free_preview' => $order === 0,
            ]);

            BootcampSectionItem::query()->create([
                'bootcamp_section_id' => $section->getKey(), 'name' => $text->name, 'order' => 2,
                'type' => SectionItemType::Lesson, 'online_lesson_id' => $text->getKey(),
                'publish_status' => PublishStatus::Published,
            ]);

            if ($order === 1) {
                $exam = $this->exam($course->name);

                BootcampSectionItem::query()->create([
                    'bootcamp_section_id' => $section->getKey(), 'name' => 'امتحان الباب', 'order' => 3,
                    'type' => SectionItemType::Exam, 'online_exam_id' => $exam->getKey(),
                    'publish_status' => PublishStatus::Published,
                ]);
            }
        }
    }

    private function exam(string $courseName): OnlineExam
    {
        $exam = OnlineExam::query()->create([
            'name' => "امتحان — {$courseName}",
            'description' => '٤ أسئلة اختيار من متعدد وسؤال مقالي. عندك ٢٠ دقيقة.',
            'allowed_chances' => 3,
            'duration_in_seconds' => 1200,
            'min_percentage' => 50,
            'published' => true,
            'show_correct_answers_after_finish' => true,
            'show_explanation' => true,
        ]);

        $mcqs = [
            ['العدد الذري لعنصر بيساوي عدد…؟', ['البروتونات', 'النيوترونات', 'الإلكترونات + النيوترونات', 'النويات']],
            ['أي من دول غاز نبيل؟', ['النيون', 'الصوديوم', 'الكلور', 'الأكسجين']],
            ['الرابطة بين ذرتي الكلور في جزيء Cl₂ رابطة…', ['تساهمية', 'أيونية', 'فلزية', 'هيدروجينية']],
            ['الأس الهيدروجيني (pH) للماء النقي عند ٢٥°م يساوي…', ['٧', '١', '١٤', '٠']],
        ];

        foreach ($mcqs as $i => [$text, $choices]) {
            $question = Question::query()->create([
                'online_exam_id' => $exam->getKey(),
                'order' => $i + 1,
                'type' => QuestionType::MultipleChoice,
                'text' => "<p>{$text}</p>",
                'explanation_text' => '<p>راجع الجزء الأول من الشرح — الإجابة الصح هي الاختيار الأول.</p>',
                'mark' => 2,
            ]);

            foreach ($choices as $c => $choice) {
                $question->choices()->create(['text' => $choice, 'order' => $c + 1, 'is_correct' => $c === 0]);
            }
        }

        Question::query()->create([
            'online_exam_id' => $exam->getKey(),
            'order' => 10,
            'type' => QuestionType::Essay,
            'text' => '<p>اشرح بالتفصيل الفرق بين الرابطة الأيونية والرابطة التساهمية مع مثال لكل واحدة.</p>',
            'mark' => 4,
        ]);

        return $exam;
    }

    /** @param list<Bootcamp> $courses */
    private function student(EducationalLevel $level, array $courses): void
    {
        $student = Student::query()->where('phone', '01000000000')->first();

        if ($student === null) {
            $student = Student::query()->create([
                'name' => 'طالب تجربة محمد أحمد',
                'phone' => '01000000000',
                'parent_phone' => '01100000000',
                'password' => 'password',
                'code' => '100000',
                'educational_level_id' => $level->getKey(),
                'governorate' => 'cairo',
                'school_type' => SchoolType::General,
                'status' => StudentStatus::Active,
            ]);

            app(WalletAction::class)->deposit($student, 300, TransactionSource::Admin, notes: 'رصيد تجربة');
        }

        $group = RechargeCardGroup::query()->firstOrCreate(['name' => 'كروت تجربة'], [
            'type' => RechargeCardType::Wallet,
            'amount' => 100,
            'cards_count' => 1,
            'is_active' => true,
        ]);

        RechargeCard::query()->firstOrCreate(['code' => '111122223333'], [
            'recharge_card_group_id' => $group->getKey(),
            'amount' => 100,
            'is_active' => true,
        ]);
    }
}
