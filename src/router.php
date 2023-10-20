<?php

declare (strict_types = 1);

//
// FICHIER :  /src/router.php
//   le moteur pour le routage
//     utilise le composant vendor/nikic/fast-route
//   Le moteur de routage vient lire les routes dans
//   le fichier routes.php
//

// Le ROUTAGE
// Lire les routes possibles
$app_routes = include 'routes.php';
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $route) use ($app_routes) {
  foreach ($app_routes as $app_route) {
    $route->addRoute($app_route[0], $app_route[1], $app_route[2]);
  }
});

// récupérer la méthode et l'URL proposée par le client
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = trim(str_replace(APP_ROOT_URL, '', $_SERVER['REQUEST_URI']));
// éliminer les paramètres (?foo=bar)
if (false !== $pos = strpos($uri, '?')) {
  $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// effectuer l'analyse de la commande
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
// traiter la commande (si c'est possible)
switch ($routeInfo[0]) {
// la commande n'existe pas
case FastRoute\Dispatcher::NOT_FOUND:
  // ... 404 Not Found
  $logger->alert("404 - commande non existante :: $uri");
  $twig->display(
    'error.html.twig',
    [
      'message' => 'La vue n\'existe pas',
    ]
  );

  break;

// la commande existe mais la méthode est incorrecte
case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
  $allowedMethods = $routeInfo[1];
  $logger->alert('405 - méthode incorrecte :: ' . $allowedMethods);
  $twig->display(
    'error.html.twig',
    [
      'message' => 'La méthode d\'appel est incorrecte',
    ]
  );

  break;

// la commande est connue
case FastRoute\Dispatcher::FOUND:
  $handler = $routeInfo[1];
  $vars = $routeInfo[2];
  // analyser la route pour détecter une écriture en contrôleur@méthode
  $params = explode('@', $handler);

  if (count($params) > 1) {
    // traiter la méthode du contrôleur
    // les contrôleurs utilisent un espace de nom
    // post@index  doit appeler Controller\PostController méthode index
    $controller = "App\Controller\\" . ucFirst($params[0]) . 'Controller';

    try {
      // la classe du contrôleur n'existe pas ...
      if (class_exists($controller) === false) {
        // historiser
        $logger->alert("500 - le contrôleur $controller n'existe pas");
        // présenter une page d'erreur
        $twig->display(
          'error.html.twig',
          [
            'message' => "Le contrôleur '$controller' n'existe pas.",
          ]
        );
        die();
      }

      $controller = new $controller();
      $action = $params[1];

      // la méthode du contrôleur n'existe pas ...
      if (method_exists($controller, $action) === false) {
        // historiser
        $logger->alert("500 - la méthode $action n'existe pas");
        // présenter une page d'erreur
        $twig->display(
          'error.html.twig',
          [
            'message' => "La méthode  '$action' du contrôleur n'existe pas.",
          ]
        );
        die();
      }
      // appeler la méthode du contrôleur
      return call_user_func_array([$controller, $action], $vars);
    } catch (Exception $exception) {
      $logger->alert(
        '404 - erreur dans le processus de traitement du routage',
        ['routeInfo' => $routeInfo, 'exception-line' => $exception->getLine(), 'exception-file' => $exception->getFile(), 'exception-code' => $exception->getMessage()]
      );
      $twig->display(
        'error.html.twig',
        [
          'message' => "La page n'existe pas.",
        ]
      );
    }
  } else {
    // appeler la fonction anonyme
    call_user_func_array($handler, $vars);
  }

  break;
}
