<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource;
use Src\Support\Infrastructure\Html\HtmlSanitizer;

final class ViewAnnouncement extends ViewRecord
{
    protected static string $resource = AnnouncementResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('content::content.announcement.sections.content'))
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('title')
                        ->label(__('content::content.announcement.fields.title')),

                    TextEntry::make('body')
                        ->label(__('content::content.announcement.fields.body'))
                        // التنقية بتحصل عند الحفظ **وعند العرض** — الاتنين
                        ->formatStateUsing(fn (?string $state): string => app(HtmlSanitizer::class)->clean($state))
                        ->html()
                        ->columnSpanFull(),
                ]),

            Section::make(__('content::content.announcement.sections.settings'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('status')->label(__('common.status'))->badge(),
                    TextEntry::make('severity')->label(__('content::content.announcement.fields.severity'))->badge(),
                    TextEntry::make('author.name')->label(__('content::content.announcement.fields.author')),
                    TextEntry::make('published_at')
                        ->label(__('content::content.announcement.fields.published_at'))
                        ->dateTime('d M Y — H:i')
                        ->placeholder('—'),
                    TextEntry::make('expires_at')
                        ->label(__('content::content.announcement.fields.expires_at'))
                        ->dateTime('d M Y — H:i')
                        ->placeholder('—'),
                    TextEntry::make('is_pinned')
                        ->label(__('content::content.announcement.fields.pinned'))
                        ->formatStateUsing(fn (bool $state): string => $state ? __('common.yes') : __('common.no')),
                ]),
        ]);
    }
}
