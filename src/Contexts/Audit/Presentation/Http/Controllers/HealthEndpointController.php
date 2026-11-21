<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;
use Spatie\Health\ResultStores\ResultStore;

/**
 * نقطة فحص الصحة لمراقب التشغيل.
 *
 * متاحة بدون مصادقة **بمفتاح** — عشان المراقب يوصلها من غير ما تبقى
 * مفتوحة للعالم. من غير مفتاح مضبوط بترجّع 404 مش 403، عشان ماتأكّدش
 * إن النقطة موجودة أصلاً (docs/20 بند ١٥).
 */
final class HealthEndpointController
{
    public function __invoke(
        Request $request,
        HealthCheckJsonResultsController $results,
        ResultStore $store,
    ): Response {
        $secret = config('security.health_endpoint_secret');

        abort_if(blank($secret), 404);
        abort_unless(hash_equals((string) $secret, (string) $request->query('secret')), 404);

        return $results($request, $store);
    }
}
