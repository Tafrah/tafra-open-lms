<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;

/** @extends Factory<Question> */
final class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'online_exam_id' => OnlineExam::factory(),
            'order' => 1,
            'type' => QuestionType::MultipleChoice,
            'text' => '<p>'.$this->faker->sentence().'؟</p>',
            'mark' => 1,
        ];
    }

    /** سؤال اختيار من متعدد بـ ٤ اختيارات، الأول هو الصح */
    public function withChoices(int $count = 4): self
    {
        return $this->afterCreating(function (Question $question) use ($count): void {
            foreach (range(1, $count) as $i) {
                $question->choices()->create([
                    'text' => "اختيار {$i}",
                    'order' => $i,
                    'is_correct' => $i === 1,
                ]);
            }
        });
    }

    public function essay(): self
    {
        return $this->state(fn (): array => ['type' => QuestionType::Essay, 'mark' => 5]);
    }
}
