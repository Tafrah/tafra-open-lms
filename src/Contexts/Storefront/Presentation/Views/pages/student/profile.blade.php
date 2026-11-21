@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.nav.profile'))

@section('content')
    <div class="mx-auto max-w-4xl space-y-6" x-data="{ panel: @js($errors->password->any() ? 'password' : ($errors->any() ? 'edit' : null)) }">
        <section class="sf-card rounded-banner p-6" aria-labelledby="profile-title">
            <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:text-start">
                <span class="grid size-20 shrink-0 place-items-center rounded-full bg-primary text-3xl font-extrabold text-white" aria-hidden="true">{{ mb_substr($student->name, 0, 1) }}</span>
                <div class="flex-1">
                    <h1 id="profile-title" class="text-xl font-extrabold text-navy">{{ $student->name }}</h1>
                    <x-storefront::status-badge :status="$student->status" class="mt-1" />
                </div>
                <div class="flex flex-wrap justify-center gap-2">
                    <button type="button" class="sf-btn-outline" x-on:click="panel = panel === 'edit' ? null : 'edit'" :aria-expanded="panel === 'edit'"><x-storefront::icon name="pencil" class="size-4" /> {{ __('storefront::storefront.profile.edit') }}</button>
                    <button type="button" class="sf-btn-outline" x-on:click="panel = panel === 'password' ? null : 'password'" :aria-expanded="panel === 'password'"><x-storefront::icon name="key" class="size-4" /> {{ __('storefront::storefront.profile.change_password') }}</button>
                </div>
            </div>
            <dl class="mt-6 grid gap-3 border-t border-line pt-5 text-sm sm:grid-cols-3">
                <div><dt class="text-body">{{ __('storefront::storefront.profile.code_label') }}</dt><dd class="font-bold text-ink" dir="ltr">{{ $student->code }}</dd></div>
                <div><dt class="text-body">{{ __('storefront::storefront.fields.phone') }}</dt><dd class="font-bold text-ink" dir="ltr">{{ $student->phone }}</dd></div>
                <div><dt class="text-body">{{ __('storefront::storefront.fields.educational_level_id') }}</dt><dd class="font-bold text-ink">{{ $student->level?->name ?? '—' }}</dd></div>
            </dl>
            <dl class="mt-5 grid grid-cols-3 gap-3">
                @foreach ([['courses', 'book', fc_number($stats['courses'])], ['exams', 'exam', fc_number($stats['exams'])], ['balance', 'wallet', fc_currency($stats['balance'])]] as [$key, $icon, $value])
                    <div class="rounded-item border-2 border-dashed border-line p-3 text-center">
                        <x-storefront::icon :name="$icon" class="mx-auto size-6 text-primary" />
                        <dd class="mt-1 text-lg font-extrabold text-navy">{{ $value }}</dd>
                        <dt class="text-xs text-body">{{ __('storefront::storefront.profile.stats.'.$key) }}</dt>
                    </div>
                @endforeach
            </dl>
        </section>

        <section x-show="panel === 'edit'" x-cloak class="sf-card p-6" aria-labelledby="edit-title">
            <h2 id="edit-title" class="mb-4 text-lg font-bold text-navy">{{ __('storefront::storefront.profile.edit') }}</h2>
            <form method="POST" action="{{ route('site.profile.update') }}" class="grid gap-4 sm:grid-cols-2" novalidate>
                @csrf
                @method('PUT')
                <div class="sm:col-span-2">
                    <label for="name" class="sf-label">{{ __('storefront::storefront.fields.name') }}</label>
                    <input id="name" name="name" type="text" required value="{{ old('name', $student->name) }}" class="sf-input" @error('name') aria-invalid="true" @enderror>
                    @error('name')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="parent_phone" class="sf-label">{{ __('storefront::storefront.fields.parent_phone') }}</label>
                    <input id="parent_phone" name="parent_phone" type="tel" value="{{ old('parent_phone', $student->parent_phone) }}" class="sf-input" @error('parent_phone') aria-invalid="true" @enderror>
                    @error('parent_phone')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="educational_level_id" class="sf-label">{{ __('storefront::storefront.fields.educational_level_id') }}</label>
                    <select id="educational_level_id" name="educational_level_id" class="sf-input" required>
                        @foreach ($levels as $level)
                            <option value="{{ $level->getKey() }}" @selected((int) old('educational_level_id', $student->educational_level_id) === (int) $level->getKey())>{{ $level->name }}</option>
                        @endforeach
                    </select>
                    @error('educational_level_id')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="sf-btn-primary sm:col-span-2 sm:justify-self-start">{{ __('storefront::storefront.actions.save') }}</button>
            </form>
        </section>

        <section x-show="panel === 'password'" x-cloak class="sf-card p-6" aria-labelledby="password-title">
            <h2 id="password-title" class="mb-4 text-lg font-bold text-navy">{{ __('storefront::storefront.profile.change_password') }}</h2>
            <form method="POST" action="{{ route('site.profile.password') }}" class="grid gap-4 sm:grid-cols-3" novalidate>
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="sf-label">{{ __('storefront::storefront.fields.current_password') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="sf-input" @error('current_password', 'password') aria-invalid="true" @enderror>
                    @error('current_password', 'password')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="new_password" class="sf-label">{{ __('storefront::storefront.fields.new_password') }}</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" required minlength="8" class="sf-input" @error('password', 'password') aria-invalid="true" @enderror>
                    @error('password', 'password')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="new_password_confirmation" class="sf-label">{{ __('storefront::storefront.fields.password_confirmation') }}</label>
                    <input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="sf-input">
                </div>
                <button type="submit" class="sf-btn-primary sm:col-span-3 sm:justify-self-start">{{ __('storefront::storefront.actions.save') }}</button>
            </form>
        </section>
    </div>
@endsection
