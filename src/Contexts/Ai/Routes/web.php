<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use Laravel\Passport\Http\Middleware\CheckToken;
use Src\Contexts\Ai\Presentation\Http\Controllers\ApproveAiConsentController;
use Src\Contexts\Ai\Presentation\Http\Controllers\OAuthMetadataController;
use Src\Contexts\Ai\Presentation\Http\Middleware\AuthenticateMcpConnection;
use Src\Contexts\Ai\Presentation\Http\Middleware\RequireConsentPrompt;
use Src\Support\Presentation\Mcp\FcMcpServer;

/*
|--------------------------------------------------------------------------
| خادم MCP وOAuth لتطبيقات الذكاء الاصطناعي (docs/23 بند ٤)
|--------------------------------------------------------------------------
| برا ستاك `web` عن قصد: مفيش جلسة ولا CSRF على `/mcp` — التوكن هو المصادقة.
| شاشة الموافقة بس جوه `web` (جلسة المستخدم وCSRF).
*/

// ── الاكتشاف (RFC 9728 · RFC 8414) ──
// اسم `mcp.oauth.protected-resource.nested` مطلوب: الباكدج بيبني منه
// `WWW-Authenticate: resource_metadata=…` على أي 401 من الخادم
Route::get('/.well-known/oauth-protected-resource', [OAuthMetadataController::class, 'protectedResource'])
    ->name('mcp.oauth.protected-resource');
Route::get('/.well-known/oauth-protected-resource/{path}', [OAuthMetadataController::class, 'protectedResource'])
    ->where('path', '.*')
    ->name('mcp.oauth.protected-resource.nested');
Route::get('/.well-known/oauth-authorization-server', [OAuthMetadataController::class, 'authorizationServer'])
    ->name('mcp.oauth.authorization-server');
Route::get('/.well-known/oauth-authorization-server/{path}', [OAuthMetadataController::class, 'authorizationServer'])
    ->where('path', '.*')
    ->name('mcp.oauth.authorization-server.nested');

// ── التسجيل الديناميكي (RFC 7591) — روابط الرجوع من `config/mcp.php` بس ──
Route::post('/oauth/register', OAuthRegisterController::class)
    ->middleware('throttle:mcp-register')
    ->name('ai.oauth.register');

// ── Passport: التوكن، وشاشة التفويض، والرفض — من غير مسار موافقة ولا JSON API ──
// (`Passport::ignoreRoutes()` في المزوّد). الأسماء زي ما Passport بيسمّيها.
Route::prefix('oauth')->name('passport.')->group(function (): void {
    Route::post('/token', [AccessTokenController::class, 'issueToken'])
        ->middleware('throttle')
        ->name('token');

    Route::get('/authorize', [AuthorizationController::class, 'authorize'])
        ->middleware(['web', RequireConsentPrompt::class])
        ->name('authorizations.authorize');

    Route::delete('/authorize', [DenyAuthorizationController::class, 'deny'])
        ->middleware(['web', 'auth:'.config('passport.guard', 'web')])
        ->name('authorizations.deny');
});

// ── الموافقة: بتسجّل المؤسسة والمستوى وبعدين بتسلّم لـ Passport ──
Route::post('/oauth/consent', ApproveAiConsentController::class)
    ->middleware(['web', 'auth:'.config('passport.guard', 'web')])
    ->name('ai.oauth.consent');

// ── الخادم ──
// الترتيب مقصود: التوكن ← النطاق ← حد المعدّل ← الربط والمؤسسة ← الأدوات
Mcp::web('/'.trim((string) config('ai.path', 'mcp'), '/'), FcMcpServer::class)
    ->middleware([
        'auth:api',
        CheckToken::using(Registrar::OAUTH_SCOPE),
        'throttle:mcp',
        AuthenticateMcpConnection::class,
    ])
    ->name('ai.mcp');
