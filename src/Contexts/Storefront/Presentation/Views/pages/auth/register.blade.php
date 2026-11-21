@extends('storefront::layouts.auth')

@section('title', __('storefront::storefront.auth.register_title'))

@section('form')
    <h1 class="text-2xl font-extrabold text-navy">{{ __('storefront::storefront.auth.register_title') }}</h1>
    <p class="mt-2 text-body">{{ __('storefront::storefront.auth.register_body') }}</p>

    <form method="POST" action="{{ route('site.register.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2" novalidate>
        @csrf

        <div class="sm:col-span-2">
            <label for="name" class="sf-label">{{ __('storefront::storefront.fields.name') }}</label>
            <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}" class="sf-input" placeholder="{{ __('storefront::storefront.fields.name_placeholder') }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')<p id="name-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="sf-label">{{ __('storefront::storefront.fields.phone') }}</label>
            <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required value="{{ old('phone') }}" class="sf-input" placeholder="{{ __('storefront::storefront.fields.phone_placeholder') }}" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
            @error('phone')<p id="phone-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="parent_phone" class="sf-label">{{ __('storefront::storefront.fields.parent_phone') }} @unless ($site->parentPhoneRequired)<span class="font-normal text-muted">{{ __('storefront::storefront.fields.optional') }}</span>@endunless</label>
            <input id="parent_phone" name="parent_phone" type="tel" inputmode="tel" value="{{ old('parent_phone') }}" class="sf-input" placeholder="{{ __('storefront::storefront.fields.phone_placeholder') }}" @if ($site->parentPhoneRequired) required @endif @error('parent_phone') aria-invalid="true" aria-describedby="parent_phone-error" @enderror>
            @error('parent_phone')<p id="parent_phone-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="sf-label">{{ __('storefront::storefront.fields.password') }}</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" class="sf-input" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')<p id="password-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="sf-label">{{ __('storefront::storefront.fields.password_confirmation') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="sf-input">
        </div>

        <div class="{{ $site->askGovernorate || $site->askSchoolType ? '' : 'sm:col-span-2' }}">
            <label for="educational_level_id" class="sf-label">{{ __('storefront::storefront.fields.educational_level_id') }}</label>
            <select id="educational_level_id" name="educational_level_id" required class="sf-input" @error('educational_level_id') aria-invalid="true" aria-describedby="level-error" @enderror>
                <option value="">{{ __('storefront::storefront.fields.choose') }}</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->getKey() }}" @selected((string) old('educational_level_id') === (string) $level->getKey())>{{ $level->name }}</option>
                @endforeach
            </select>
            @error('educational_level_id')<p id="level-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
        </div>

        @if ($site->askGovernorate)
            <div>
                <label for="governorate" class="sf-label">{{ __('storefront::storefront.fields.governorate') }}</label>
                <select id="governorate" name="governorate" required class="sf-input" @error('governorate') aria-invalid="true" aria-describedby="governorate-error" @enderror>
                    <option value="">{{ __('storefront::storefront.fields.choose') }}</option>
                    @foreach ($governorates as $key => $label)
                        <option value="{{ $key }}" @selected(old('governorate') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('governorate')<p id="governorate-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
            </div>
        @endif

        @if ($site->askSchoolType)
            <fieldset class="{{ $site->askGovernorate ? 'sm:col-span-2' : '' }}">
                <legend class="sf-label">{{ __('storefront::storefront.fields.school_type') }}</legend>
                <div class="flex gap-3">
                    @foreach (\Src\Contexts\Students\Domain\Enums\SchoolType::cases() as $type)
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-card border border-line px-4 py-3 text-sm font-bold has-[:checked]:border-primary has-[:checked]:bg-primary/10 has-[:checked]:text-primary">
                            <input type="radio" name="school_type" value="{{ $type->value }}" class="text-primary focus:ring-primary" @checked(old('school_type', 'general') === $type->value)>
                            {{ $type->getLabel() }}
                        </label>
                    @endforeach
                </div>
                @error('school_type')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
            </fieldset>
        @endif

        <button type="submit" class="sf-btn-primary w-full py-3 text-base sm:col-span-2">{{ __('storefront::storefront.auth.create_account') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-body">
        {{ __('storefront::storefront.auth.have_account') }}
        <a href="{{ route('site.login') }}" class="font-bold text-primary hover:underline">{{ __('storefront::storefront.nav.login') }}</a>
    </p>
@endsection
