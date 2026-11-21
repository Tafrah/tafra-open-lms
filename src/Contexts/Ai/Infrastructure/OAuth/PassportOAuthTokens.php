<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Infrastructure\OAuth;

use Laravel\Passport\AuthCode;
use Laravel\Passport\Client;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Src\Contexts\Ai\Application\Contracts\OAuthTokens;

final readonly class PassportOAuthTokens implements OAuthTokens
{
    public function revokeFor(int $userId, string $clientId): void
    {
        $tokenIds = Token::query()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->pluck('id');

        Token::query()->whereIn('id', $tokenIds)->update(['revoked' => true]);

        // توكن التجديد هو الأخطر: من غيره الإلغاء بيدوم لحد ما التوكن يخلص بس
        RefreshToken::query()->whereIn('access_token_id', $tokenIds)->update(['revoked' => true]);

        // كود موافقة لسه ماتبدّلش بتوكن — يتقفل برضه
        AuthCode::query()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->update(['revoked' => true]);
    }

    public function clientName(string $clientId): ?string
    {
        $name = Client::query()->whereKey($clientId)->value('name');

        return is_string($name) ? $name : null;
    }
}
