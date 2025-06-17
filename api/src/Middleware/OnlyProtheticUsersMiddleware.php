<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface;

class OnlyProtheticUsersMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            $response = new \Slim\Psr7\Response();
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        // Proceed with the next middleware
        return $handler->handle($request);
    }
}