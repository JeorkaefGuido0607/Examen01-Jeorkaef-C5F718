<?php
$container->set('config', function() {
    return (object) [
        'api_data' => rtrim($_ENV['API_DATA'], '/'),
        'timeout' => (float) ($_ENV['API_TIMEOUT'] ?? 5)
    ];
});
$container->set(\App\Services\DataService::class, function($container) {
    return new \App\Services\DataService($container->get('config'));
});
