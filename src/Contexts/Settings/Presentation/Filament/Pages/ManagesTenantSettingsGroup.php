<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Actions\UpdateTenantSettingsAction;
use Src\Contexts\Settings\Application\Services\TenantSettings;

/**
 * صفحة بتعدّل **مجموعة** إعدادات للمؤسسة الحالية.
 *
 * القراءة من `TenantSettings::get()` — القيمة الفاضية بترجع للإعداد العام،
 * فالنموذج بيبدأ بالقيمة الفعّالة مش بخانات فاضية. الكتابة عبر
 * `UpdateTenantSettingsAction` (الـ Policy آخر حاجز).
 */
trait ManagesTenantSettingsGroup
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    abstract protected static function group(): string;

    /**
     * المفاتيح اللي الصفحة بتديرها وقيمتها لو مفيش ولا عام ولا مؤسسة.
     *
     * @return array<string, mixed>
     */
    abstract protected static function defaults(): array;

    public function mount(): void
    {
        $settings = app(TenantSettings::class);
        $values = [];

        foreach (static::defaults() as $key => $default) {
            $values[$key] = $settings->get(static::group(), $key, $default);
        }

        $this->form->fill($values);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('common.save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        /** @var array<string, mixed> $state */
        $state = $this->form->getState();

        app(UpdateTenantSettingsAction::class)->handle(static::group(), $state, $actor);

        Notification::make()
            ->success()
            ->title(__('settings::settings.saved'))
            ->body(__('settings::settings.saved_body'))
            ->send();
    }
}
