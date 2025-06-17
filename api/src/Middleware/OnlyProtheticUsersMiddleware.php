<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface;

class OnlyProtheticUsersMiddleware
{
    public function __construct(
        private string $keycloakRealmUrl,
        private string $clientId,
        private string $requiredRole
    ) {
    }

    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new \Exception('Invalid Authorization header format', 401);
        }

        $jwt = $matches[1];

        try {
            $keycloakCerts = json_decode(
                file_get_contents($this->keycloakRealmUrl . '/protocol/openid-connect/certs'),
                true
            );

            $publicKey = $keycloakCerts['keys'][0]['x5c'][0];
            $publicKey = "-----BEGIN CERTIFICATE-----\n" . $publicKey . "\n-----END CERTIFICATE-----";

            $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

            // Проверка аудитории
            if (!in_array($this->clientId, (array)$decoded->aud)) {
                throw new \Exception('Invalid token audience', 403);
            }

            // Проверка роли
            $hasRole = $this->checkRole($decoded, $this->requiredRole);

            if (!$hasRole) {
                throw new \Exception('Insufficient permissions', 403);
            }

            // Добавляем декодированный токен в атрибуты запроса
            $request = $request->withAttribute('token', $decoded);

            return $handler->handle($request);
        }
        catch (\Exception $e) {
            throw new \Exception('Token validation failed: ' . $e->getMessage(), 401);
        }
    }


    private function checkRole(object $decoded, string $requiredRole): bool
    {
        // Проверка ролей клиента
        if (isset($decoded->resource_access->{$this->clientId}->roles)) {
            if (in_array($requiredRole, $decoded->resource_access->{$this->clientId}->roles)) {
                return true;
            }
        }

        // Проверка realm ролей
        if (isset($decoded->realm_access->roles)) {
            if (in_array($requiredRole, $decoded->realm_access->roles)) {
                return true;
            }
        }

        return false;
    }
}
