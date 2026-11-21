<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Src\Contexts\Ai\Application\Actions\AuthorizeAiConnectionAction;
use Src\Contexts\Ai\Application\DataObjects\AuthorizeAiConnectionData;
use Src\Contexts\Ai\Domain\Enums\AiAccessLevel;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * زرار «سماح» في شاشة الموافقة — الطريق **الوحيد** لإصدار كود تفويض
 * (مسار الموافقة بتاع Passport مش متسجّل — `AiServiceProvider`).
 *
 * بيسجّل الربط (المؤسسة والمستوى اللي المستخدم اختارهم) عن طريق الـ Action
 * — وهو اللي بيعمل `Gate::authorize('connect')` — وبعدين بيسلّم لـ Passport
 * يصدر الكود. العميل جاي من طلب التفويض المحفوظ في الجلسة، **مش** من الفورم:
 * فورم معدّل مايقدرش يربط مؤسسة بعميل تاني.
 *
 * الاتنين في transaction واحدة على القاعدة المركزية (جداول Passport والربط
 * هناك): لو Passport ماطلّعش كود، تبديل المؤسسة وإلغاء التوكنز القديمة
 * بيرجعوا — بدل ما المستخدم يتفصل من غير كود جديد.
 */
final class ApproveAiConsentController
{
    public function __invoke(
        Request $request,
        ResponseInterface $psrResponse,
        AuthorizeAiConnectionAction $authorize,
        ApproveAuthorizationController $passport,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $input = $request->validate([
            'auth_token' => ['required', 'string'],
            'tenant' => ['required', 'string', 'max:32'],
            'access' => ['required', Rule::enum(AiAccessLevel::class)],
        ]);

        abort_unless(hash_equals((string) $request->session()->get('authToken'), (string) $input['auth_token']), 403);

        // الجلسة على السيرفر — مش مدخل من المستخدم
        $authRequest = unserialize((string) $request->session()->get('authRequest'));

        abort_unless($authRequest instanceof AuthorizationRequestInterface, 403);

        $data = new AuthorizeAiConnectionData(
            clientId: (string) $authRequest->getClient()->getIdentifier(),
            tenantId: $input['tenant'] === 'central' ? null : (int) $input['tenant'],
            access: AiAccessLevel::from((string) $input['access']),
        );

        $failed = null;

        try {
            return DB::connection((new AiConnection)->getConnectionName())->transaction(
                function () use ($authorize, $data, $user, $passport, $request, $psrResponse, &$failed): Response {
                    $authorize->handle($data, $user);

                    // Passport بيقرا `auth_token` ويسحب الطلب من الجلسة ويصدر الكود
                    $response = $passport->approve($request, $psrResponse);

                    if (! $this->issuedCode($response)) {
                        $failed = $response;

                        throw new RuntimeException('authorization code was not issued');
                    }

                    return $response;
                },
            );
        } catch (RuntimeException $exception) {
            // رد Passport بالخطأ (redirect فيه `error=`) — والربط رجع زي ما كان
            if ($failed instanceof Response) {
                return $failed;
            }

            throw $exception;
        }
    }

    private function issuedCode(Response $response): bool
    {
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return $response->isRedirection() && isset($query['code']) && ! isset($query['error']);
    }
}
