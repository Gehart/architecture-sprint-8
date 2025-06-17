<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Инициализация приложения
$app = AppFactory::create();

$app->addErrorMiddleware(true, false, false); 

$beforeMiddleware = function (Request $request, RequestHandler $handler) use ($app) {
    // Example: Check for a specific header before proceeding
    $auth = $request->getHeaderLine('Authorization');
    if (!$auth) {
        // Short-circuit and return a response immediately
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write('Unauthorized');
        
        return $response->withStatus(401);
    }

    // Proceed with the next middleware
    return $handler->handle($request);
};

// Единственный роут - GET /api/hello
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
    ->add(new App\Middleware\OnlyProtheticUsersMiddleware());
    // ->add($beforeMiddleware);


// Обработка 404 ошибки для всех остальных запросов
// $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], '/{routes:.+}', function ($request, $response) {
//     return $response
//         ->withHeader('Content-Type', 'application/json')
//         ->withStatus(404);
// });

// Запуск приложения
$app->run();