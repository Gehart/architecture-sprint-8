<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class OnlyProtheticUsersMiddleware
{
    public function __construct(
        private string $keycloakRealmUrl,
        private string $requiredRole
    ) {
    }

    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');

            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                throw new \Exception('Invalid Authorization header format', 401);
            }

            $jwt = $matches[1];

            $keycloakCerts = json_decode(
                file_get_contents($this->keycloakRealmUrl . '/protocol/openid-connect/certs'),
                true
            );

            $publicKey = $keycloakCerts['keys'][0]['x5c'][0];
            $publicKey = "-----BEGIN CERTIFICATE-----\n" . $publicKey . "\n-----END CERTIFICATE-----";

            $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

            $hasRole = $this->checkRole($decoded, $this->requiredRole);

            if (!$hasRole) {
                throw new \Exception('Insufficient permissions', 403);
            }

            return $handler->handle($request);
        }
        catch (\Throwable $e) {
            $response = new \Slim\Psr7\Response();
        
              return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
        }
    }

    private function checkRole(object $decoded, string $requiredRole): bool
    {
        if (isset($decoded->realm_access->roles)) {
            if (in_array($requiredRole, $decoded->realm_access->roles)) {
                return true;
            }
        }

        return false;
    }
}
