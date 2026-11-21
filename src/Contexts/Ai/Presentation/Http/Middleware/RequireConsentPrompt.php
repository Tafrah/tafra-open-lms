<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * شاشة الموافقة **دايماً** — مفيش موافقة تلقائية.
 *
 * Passport بيوافق لوحده لو المستخدم عنده توكن شغّال لنفس العميل
 * (`hasGrantedScopes`) — والطريق ده مابيعدّيش على `connect` ولا على اختيار
 * المؤسسة: منتحل يعرف `client_id` كان يطلع كود باسم اللي بينتحله، والمستخدم
 * اللي عايز يبدّل المؤسسة ماكانش بيشوف الشاشة تاني. `prompt=consent` بيقفل
 * الطريق ده من جوه Passport نفسه.
 */
final class RequireConsentPrompt
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->query->set('prompt', 'consent');
        $request->merge(['prompt' => 'consent']);

        return $next($request);
    }
}
