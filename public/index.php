<?php

declare(strict_types=1);

use MaxServ\Core\Bootstrap;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

DEFINE('APPLICATION_ROOT', dirname(__DIR__));

require_once APPLICATION_ROOT . '/vendor/autoload.php';

$bootstrap = new Bootstrap();

try {
    $bootstrap->boot();
} catch (ResourceNotFoundException) {
    (new Response('404 - Page not found', Response::HTTP_NOT_FOUND))->send();
} catch (Throwable $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    (new Response('500 - Internal Server Error', Response::HTTP_INTERNAL_SERVER_ERROR))->send();
}
