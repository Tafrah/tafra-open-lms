<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Src\Contexts\Installer\Application\DataObjects\AcademyData;

final class AcademyStepRequest extends FormRequest
{
    /** @var list<string> */
    protected $dontFlash = ['admin_password', 'admin_password_confirmation'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return AcademyData::rules(confirmPassword: true);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (array) __('installer::installer.academy.fields');
    }
}
