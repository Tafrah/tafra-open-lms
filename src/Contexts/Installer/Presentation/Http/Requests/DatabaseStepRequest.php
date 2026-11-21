<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Src\Contexts\Installer\Application\DataObjects\DatabaseCredentials;

final class DatabaseStepRequest extends FormRequest
{
    /**
     * كلمة السر مابتترجعش للفورم ولا بتتحفظ كـ old input.
     *
     * @var list<string>
     */
    protected $dontFlash = ['db_password'];

    public function authorize(): bool
    {
        // قبل التثبيت مفيش مستخدمين — الحماية هي `EnsureNotInstalled`
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return DatabaseCredentials::rules();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (array) __('installer::installer.database.fields');
    }
}
