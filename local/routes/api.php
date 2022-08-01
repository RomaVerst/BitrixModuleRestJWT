<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use NightPilgrim\RestApi\Controllers\Api\UserController;

return function (RoutingConfigurator $routes) {
    $routes->prefix('local/rest')->group(function (RoutingConfigurator $routes) {
        $routes->post('register', [UserController::class, 'register']);
        $routes->post('auth', [UserController::class, 'auth']);
        $routes->post('users', [UserController::class, 'showUsers']);
        $routes->post('users/{id}', [UserController::class, 'showOneUser']);
    });
};