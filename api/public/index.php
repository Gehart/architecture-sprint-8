<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use App\Middleware\OnlyProtheticUsersMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$isDevMode = getenv('APP_ENV') === 'dev';
$app->addErrorMiddleware($isDevMode ?: false, false, false);

$onlyProtheticUsersMiddleware = new OnlyProtheticUsersMiddleware(
    getenv('API_KEYCLOAK_URL'),
    'prothetic_user'
);

$app->get('/reports', function (Request $request, Response $response, array $args) {
    $faker = Faker\Factory::create();

    $data = [[
        'id' => $faker->uuid(),
        'name' => $faker->name(),
        'note' => $faker->text(),
        'status' => 'success',
        'timestamp' => time()
    ]];
    
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
})
    ->add($onlyProtheticUsersMiddleware);

$app->run();
