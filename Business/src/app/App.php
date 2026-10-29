<?php

use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../../vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable('/var/www/html');
$dotenv->load();

$container = new Container();

AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

require_once "config.php";
require_once "routes.php";

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(
    \Slim\Exception\HttpNotFoundException::class,
    function ($request, $exception, bool $displayErrorDetails) use ($app) {
        $response = $app->getResponseFactory()->createResponse(404);
        $response->getBody()->write(json_encode([
            'error' => 'Ruta no encontrada.'
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
);
$errorMiddleware->setErrorHandler(
    \Slim\Exception\HttpMethodNotAllowedException::class,
    function ($request, \Slim\Exception\HttpMethodNotAllowedException $exception, bool $displayErrorDetails) use ($app) {
        $response = $app->getResponseFactory()->createResponse(405);
        $response->getBody()->write(json_encode([
            'error' => 'Método no permitido.',
            'metodos_permitidos' => $exception->getAllowedMethods()
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
);

$app->run();
