<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Mcp;

use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Content\Application\Actions\PublishAnnouncementAction;
use Src\Contexts\Content\Application\Actions\TogglePinAnnouncementAction;
use Src\Contexts\Content\Application\DataObjects\PublishAnnouncementData;
use Src\Contexts\Content\Domain\Enums\AnnouncementSeverity;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpRecordAction;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * الإعلانات للذكاء الاصطناعي — المثال المرجعي لإعلان مورد كامل (docs/23 بند ٣).
 *
 * نفس الفورم ونفس أزرار الجدول: إنشاء وتعديل وحذف واسترجاع، ونشر وتثبيت
 * بنفس الـ Actions. الحالة مابتتكتبش مباشرة — بتتغيّر بالنشر بس، زي اللوحة.
 */
final class AnnouncementMcp extends McpResource
{
    private const LABELS = 'content::content.announcement.fields.';

    public static function filamentResource(): string
    {
        return AnnouncementResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('title')
                ->translatable()
                ->required()
                ->max((int) config('content.title_max_length', 180))
                ->label(self::LABELS.'title'),

            McpField::html('body')
                ->translatable()
                ->required()
                ->label(self::LABELS.'body'),

            McpField::enum('status', AnnouncementStatus::class)
                ->readOnly()
                ->filterable()
                ->label('common.status')
                ->hint('content::content.mcp.hints.status'),

            McpField::enum('severity', AnnouncementSeverity::class)
                ->required()
                ->filterable()
                ->label(self::LABELS.'severity'),

            McpField::boolean('is_pinned')
                ->readOnly()
                ->filterable()
                ->label(self::LABELS.'pinned'),

            // التواريخ دي nullable — مش قابلة للترتيب بالـ cursor (`McpField::sortable()`)
            McpField::dateTime('published_at')
                ->label(self::LABELS.'published_at'),

            McpField::dateTime('expires_at')
                ->after('published_at')
                ->label(self::LABELS.'expires_at'),
        ];
    }

    public function operations(): array
    {
        return RecordOperation::cases();
    }

    public function actions(): array
    {
        return [
            McpRecordAction::make('publish')
                ->label('content::content.announcement.actions.publish')
                ->description('content::content.mcp.actions.publish')
                ->fields([
                    McpField::boolean('notify_users')->label(self::LABELS.'notify_users'),
                    McpField::dateTime('publish_at')->label(self::LABELS.'published_at'),
                ])
                ->using(fn (Model $record, array $input, User $actor): Announcement => app(PublishAnnouncementAction::class)->handle(
                    PublishAnnouncementData::fromArray([
                        'id' => $record->getKey(),
                        'notify_users' => $input['notify_users'] ?? true,
                        'publish_at' => $input['publish_at'] ?? null,
                    ]),
                    $actor,
                )),

            McpRecordAction::make('pin')
                ->label('content::content.announcement.actions.pin')
                ->description('content::content.mcp.actions.pin')
                ->safe()
                ->using(fn (Model $record, array $input, User $actor): Announcement => app(TogglePinAnnouncementAction::class)
                    ->handle($this->announcement($record), $actor)),
        ];
    }

    public function omitted(): array
    {
        return [
            'export' => 'mcp.omitted.export',
        ];
    }

    /** الكاتب = المستخدم الحالي، زي `CreateAnnouncement::mutateFormDataBeforeCreate()`. */
    public function creating(array $attributes, User $actor): array
    {
        return [...$attributes, 'author_id' => $actor->getKey()];
    }

    public function with(): array
    {
        return ['author:id,name'];
    }

    public function extra(Model $record): array
    {
        $author = $record->getRelationValue('author');

        return ['author' => $author?->getAttribute('name')];
    }

    private function announcement(Model $record): Announcement
    {
        // السجل جاي من استعلام المورد نفسه — أي نوع تاني = خطأ برمجي
        throw_unless($record instanceof Announcement, \LogicException::class);

        return $record;
    }
}
