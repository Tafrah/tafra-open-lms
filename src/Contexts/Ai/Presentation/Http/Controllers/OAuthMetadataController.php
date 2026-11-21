<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Laravel\Mcp\Server\Registrar;

/**
 * اكتشاف OAuth للعملاء (Claude وChatGPT) — RFC 9728 وRFC 8414.
 *
 * بنكتبها بنفسنا بدل `Mcp::oauthRoutes()`: ChatGPT بيشترط
 * `token_endpoint_auth_methods_supported`، والباكدج مابيرجّعهاش. ومكان
 * واحد للمستندين بيمنع إن نسخة منهم تقول حاجة والتانية حاجة.
 */
final class OAuthMetadataController
{
    /**
     * RFC 9728 — `resource` لازم يساوي رابط الخادم بالظبط، فبنرد على مسار
     * الخادم بس. أي مسار تاني 404 بدل ما نعلن موارد مش موجودة.
     */
    public function protectedResource(?string $path = null): JsonResponse
    {
        $mcpPath = trim((string) config('ai.path', 'mcp'), '/');
        $path = trim((string) $path, '/');

        abort_unless($path === '' || $path === $mcpPath, 404);

        return new JsonResponse([
            'resource' => url('/'.$mcpPath),
            'authorization_servers' => [$this->issuer()],
            'scopes_supported' => [Registrar::OAUTH_SCOPE],
            'bearer_methods_supported' => ['header'],
            'resource_name' => (string) config('app.name'),
        ]);
    }

    /** RFC 8414 */
    public function authorizationServer(): JsonResponse
    {
        return new JsonResponse([
            'issuer' => $this->issuer(),
            'authorization_endpoint' => route('passport.authorizations.authorize'),
            'token_endpoint' => route('passport.token'),
            'registration_endpoint' => route('ai.oauth.register'),
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            // عملاء عامّين بـ PKCE — مفيش سر عند العميل يتسرّب
            'token_endpoint_auth_methods_supported' => ['none'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => [Registrar::OAUTH_SCOPE],
        ]);
    }

    private function issuer(): string
    {
        return rtrim((string) (config('mcp.authorization_server') ?? url('/')), '/');
    }
}
