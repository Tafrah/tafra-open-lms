<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** تبديل اللغة للموقع العام — نفس مفتاح الجلسة اللي `SetLocale` بيقراه. */
final class LocaleController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in((array) config('app.supported_locales', ['ar', 'en']))],
        ]);

        $request->session()->put('locale', $data['locale']);

        return redirect()->back(fallback: route('site.home'));
    }
}
