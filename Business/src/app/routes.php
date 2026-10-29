<?php
use App\Controllers\CitaSalonController;
use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function (RouteCollectorProxy $api) {
    $api->group('/citas_salon', function (RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', CitaSalonController::class . ':read');
        $endpoint->post('', CitaSalonController::class . ':create');
        $endpoint->put('/{id}', CitaSalonController::class . ':update');
        $endpoint->delete('/{id}', CitaSalonController::class . ':delete');
    });
});
