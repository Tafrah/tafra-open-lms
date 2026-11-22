<?php

declare(strict_types=1);

use Filament\Forms\Components\Repeater;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Exams\Application\Actions\ImportQuestionsAction;
use Src\Contexts\Exams\Application\Services\QuestionCsv;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Pages\CreateOnlineExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Pages\EditOnlineExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\RelationManagers\QuestionsRelationManager;
use Src\Support\Application\Contracts\DiskResolver;

beforeEach(fn () => inTenant());

function csvOf(array $rows, ?array $headers = null): string
{
    $headers ??= app(QuestionCsv::class)->headers();
    $lines = [implode(',', $headers)];

    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(fn (string $v): string => '"'.str_replace('"', '""', $v).'"', $row));
    }

    return implode("\r\n", $lines)."\r\n";
}

// ═══════════ استيراد أسئلة من CSV ═══════════

it('الاستيراد بيضيف أسئلة الاختيار من متعدد بترتيبها واختياراتها والإجابة الصح والدرجة والتفسير', function (): void {
    $admin = actingAsRole('admin');
    $exam = OnlineExam::factory()->create();
    Question::factory()->withChoices()->create(['online_exam_id' => $exam->getKey(), 'order' => 5]);

    $result = app(ImportQuestionsAction::class)->handle($exam, csvOf([
        ['عاصمة مصر؟', 'القاهرة', 'أسوان', '', '', '', '', '1', '2', 'معلومة'],
        ['٢ + ٢ = ؟', '٣', '', '٤', '', '', '', '٣', '', ''],
    ]), $admin);

    expect($result->failed())->toBeFalse()->and($result->imported)->toBe(2);

    $imported = Question::query()->where('online_exam_id', $exam->getKey())->where('order', '>', 5)->orderBy('order')->with('choices')->get();
    [$first, $second] = $imported->all();

    expect($imported)->toHaveCount(2)
        ->and($first->order)->toBe(6)
        ->and($first->type)->toBe(QuestionType::MultipleChoice)
        ->and($first->text)->toContain('عاصمة مصر؟')
        ->and((float) $first->mark)->toBe(2.0)
        ->and($first->explanation_text)->toBe('معلومة')
        ->and($first->choices->pluck('text')->all())->toBe(['القاهرة', 'أسوان'])
        ->and($first->choices->firstWhere('is_correct', true)->text)->toBe('القاهرة')
        // العمود الفاضي في النص بيتشال، والصح = العمود ٣
        ->and($second->choices->pluck('text')->all())->toBe(['٣', '٤'])
        ->and($second->choices->firstWhere('is_correct', true)->text)->toBe('٤')
        ->and((float) $second->mark)->toBe(1.0)
        ->and($exam->fresh()->questions_number)->toBe(3);
});

it('صف واحد غلط = ولا سؤال بيتضاف، والأخطاء برقم الصف', function (): void {
    $admin = actingAsRole('admin');
    $exam = OnlineExam::factory()->create();

    $result = app(ImportQuestionsAction::class)->handle($exam, csvOf([
        ['سليم', 'أ', 'ب', '', '', '', '', '1', '1', ''],
        ['', 'أ', '', '', '', '', '', '4', 'x', ''],
    ]), $admin);

    expect($result->failed())->toBeTrue()
        ->and($result->imported)->toBe(0)
        ->and(array_keys($result->errors))->toBe([3])
        ->and($result->errors[3])->toHaveCount(4)
        ->and($result->errorLines())->toContain(__('exams::exams.import.row_error', ['row' => 3, 'errors' => '']))
        ->and(Question::query()->where('online_exam_id', $exam->getKey())->count())->toBe(0);
});

it('ملف ناقص أعمدة أو فاضي بيترفض برسالة واضحة', function (): void {
    $admin = actingAsRole('admin');
    $exam = OnlineExam::factory()->create();

    expect(fn () => app(ImportQuestionsAction::class)->handle($exam, "title,answer\nx,y\n", $admin))
        ->toThrow(ExamsException::class)
        ->and(fn () => app(ImportQuestionsAction::class)->handle($exam, csvOf([]), $admin))
        ->toThrow(ExamsException::class, __('exams::exams.errors.import_empty'));
});

it('ملف Excel عربي (Windows-1256 بفاصلة منقوطة) بيتقري صح', function (): void {
    if (! function_exists('iconv')) {
        $this->markTestSkipped('iconv');
    }

    $admin = actingAsRole('admin');
    $exam = OnlineExam::factory()->create();
    $utf8 = "question;choice_1;choice_2;correct\nسؤال عربي;نعم;لا;2\n";

    $result = app(ImportQuestionsAction::class)->handle($exam, (string) iconv('UTF-8', 'CP1256', $utf8), $admin);

    expect($result->imported)->toBe(1)
        ->and(Question::query()->where('online_exam_id', $exam->getKey())->firstOrFail()->text)->toContain('سؤال عربي');
});

it('القالب فيه الأعمدة وأمثلة عربي، ونفسه بيتستورد من غير أخطاء', function (): void {
    $admin = actingAsRole('admin');
    $exam = OnlineExam::factory()->create();
    $template = app(QuestionCsv::class)->template();

    expect($template)->toStartWith("\u{FEFF}question,choice_1")
        ->toContain('عاصمة مصر');

    $result = app(ImportQuestionsAction::class)->handle($exam, $template, $admin);
    expect($result->failed())->toBeFalse()->and($result->imported)->toBe(count((array) __('exams::exams.import.samples')));
});

it('الاستيراد محتاج صلاحية تعديل الامتحان، والمساعد مايشوفش الزرار', function (): void {
    $exam = OnlineExam::factory()->create();

    $assistant = actingAsRole('assistant');
    expect(fn () => app(ImportQuestionsAction::class)->handle($exam, csvOf([['س', 'أ', 'ب', '', '', '', '', '1', '', '']]), $assistant))
        ->toThrow(AuthorizationException::class);

    Livewire::test(QuestionsRelationManager::class, ['ownerRecord' => $exam, 'pageClass' => EditOnlineExam::class])
        ->assertTableActionHidden('importQuestions')
        ->assertTableActionHidden('importTemplate');
});

it('زرار الاستيراد في شاشة الأسئلة بيرفع الملف ويضيف الأسئلة، وزرار القالب بينزّل CSV', function (): void {
    actingAsRole('admin');
    $exam = OnlineExam::factory()->create();
    $file = UploadedFile::fake()->createWithContent('questions.csv', csvOf([['س١', 'أ', 'ب', '', '', '', '', '2', '1', '']]));

    Livewire::test(QuestionsRelationManager::class, ['ownerRecord' => $exam, 'pageClass' => EditOnlineExam::class])
        ->assertTableActionVisible('importQuestions')
        ->callTableAction('importQuestions', data: ['file' => $file])
        ->assertHasNoTableActionErrors()
        ->assertNotified()
        ->callTableAction('importTemplate')
        ->assertFileDownloaded('questions-template.csv');

    expect(Question::query()->where('online_exam_id', $exam->getKey())->firstOrFail()->choices()->where('is_correct', true)->value('text'))->toBe('ب');
});

// ═══════════ صور السؤال والاختيار والتفسير + السماح بصور الإجابة ═══════════

it('المدرس بيرفع صورة للسؤال وللاختيار وللتفسير ويسمح بصور الإجابة في المقالي', function (): void {
    foreach (['image', 'choice_image', 'explanation_image'] as $collection) {
        Storage::fake(app(DiskResolver::class)->for($collection));
    }

    actingAsRole('admin');
    $exam = OnlineExam::factory()->create();
    $undo = Repeater::fake();

    Livewire::test(QuestionsRelationManager::class, ['ownerRecord' => $exam, 'pageClass' => EditOnlineExam::class])
        ->callTableAction('create', data: [
            'type' => QuestionType::MultipleChoice->value,
            'mark' => 1,
            'text' => '<p>Look at the picture</p>',
            'image' => [UploadedFile::fake()->image('q.png')],
            'explanation_image' => [UploadedFile::fake()->image('why.png')],
            'choices' => [
                ['text' => 'A', 'is_correct' => true, 'choice_image' => [UploadedFile::fake()->image('a.png')]],
                ['text' => 'B', 'is_correct' => false, 'choice_image' => []],
            ],
        ])
        ->assertHasNoTableActionErrors();

    $undo();

    $question = Question::query()->where('online_exam_id', $exam->getKey())->with('choices')->firstOrFail();
    expect($question->getFirstMedia('image'))->not->toBeNull()
        ->and($question->getFirstMedia('explanation_image'))->not->toBeNull()
        ->and($question->choices->firstWhere('text', 'A')->getFirstMedia('choice_image'))->not->toBeNull()
        ->and($question->choices->firstWhere('text', 'B')->getFirstMedia('choice_image'))->toBeNull();

    Livewire::test(QuestionsRelationManager::class, ['ownerRecord' => $exam, 'pageClass' => EditOnlineExam::class])
        ->callTableAction('create', data: [
            'type' => QuestionType::Essay->value,
            'mark' => 5,
            'text' => '<p>Solve and photograph</p>',
            'essay' => ['ideal_answer' => 'x', 'allow_images' => true],
        ])
        ->assertHasNoTableActionErrors();

    expect(Question::query()->where('type', QuestionType::Essay)->firstOrFail()->essay->allow_images)->toBeTrue();
});

// ═══════════ «إظهار الإجابات»: لأ · بعد التسليم · بعد تاريخ ═══════════

it('فورم الامتحان: «بعد تاريخ» بيحفظ التاريخ، و«ماتظهرش» بيقفل الإجابات ويفضّي التاريخ', function (): void {
    actingAsRole('admin');

    Livewire::test(CreateOnlineExam::class)
        ->fillForm([
            'name' => 'Dated',
            'min_percentage' => 50,
            'show_answers_mode' => 'after_date',
            'show_answers_at' => now()->addDays(3)->format('Y-m-d H:i'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $exam = OnlineExam::query()->where('name', 'Dated')->firstOrFail();
    expect($exam->show_correct_answers_after_finish)->toBeTrue()
        ->and($exam->show_answers_at)->not->toBeNull()
        ->and($exam->answersVisibleNow())->toBeFalse()
        ->and($exam->answersScheduled())->toBeTrue();

    Livewire::test(EditOnlineExam::class, ['record' => $exam->getKey()])
        ->assertFormSet(['show_answers_mode' => 'after_date'])
        ->fillForm(['show_answers_mode' => 'never'])
        ->call('save')
        ->assertHasNoFormErrors();

    $exam->refresh();
    expect($exam->show_correct_answers_after_finish)->toBeFalse()
        ->and($exam->show_answers_at)->toBeNull()
        ->and($exam->answersVisibleNow())->toBeFalse();

    Livewire::test(EditOnlineExam::class, ['record' => $exam->getKey()])
        ->fillForm(['show_answers_mode' => 'after_finish'])
        ->call('save');

    expect($exam->fresh()->answersVisibleNow())->toBeTrue();
});

it('«بعد تاريخ» من غير تاريخ = خطأ في الفورم', function (): void {
    actingAsRole('admin');

    Livewire::test(CreateOnlineExam::class)
        ->fillForm(['name' => 'X', 'min_percentage' => 50, 'show_answers_mode' => 'after_date', 'show_answers_at' => null])
        ->call('create')
        ->assertHasFormErrors(['show_answers_at' => 'required']);
});
