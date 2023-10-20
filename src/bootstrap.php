<?php

declare(strict_types=1);
/*
  Fichier : bootstrap.php

*/

/** 1 initialiser COMPOSER */
require '../vendor/autoload.php';

/** 2 */
// les constantes diverses et celles de configuration pour la base
require 'Config/config.php';

// 3** logger
// https://packagist.org/packages/katzgrau/klogger
//   $logger->info()
//   $logger->error()
//   $logger->debug()
$logger = new Katzgrau\KLogger\Logger(APP_DEBUG_FILE_PATH, Psr\Log\LogLevel::DEBUG, [
    'extension' => 'log',
    'dateFormat' => 'Y-m-d G:i:s',
    'logFormat' => "[{date}]\t[{level}]\t{message}",
]);


/** 4 */
// Gestion/Affichage des erreurs
$whoops = new \Whoops\Run();
if (APP_MODE === 'dev') {
    // en mode DEV, les erreurs sont présentées à l'écran dans une interface adaptée
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
} else {
    // en mode PROD, les erreurs sont enregistrées dans le fichier
    //   src\Storage\error.log
    $whoops->pushHandler(function ($exception, $inspector, $run) {
        $errorFile = APP_STORAGE_DIRECTORY . 'error.log';
        $output = date('Y-m-d H:i:s') . ' L' . $exception->getLine() . ' ' .
                  $exception->getFile() . ':: ' . $exception->getMessage() . "\n" .
                  file_get_contents($errorFile);
        file_put_contents($errorFile, $output);
    });
}
$whoops->register();

/** 5 */
// composant TWIG
$twig = new \Twig\Environment(
    // spécifier le dossier des templates twig
    new \Twig\Loader\FilesystemLoader(APP_SRC_DIRECTORY . 'View'),
    [
        // le dossier du cache de TWIG – pas actif pour le moment
        'cache' => false,
        // si true, les variables qui n'existent pas déclenchent une erreur
        'strict_variables ' => true,
        // debug activé
        'debug' => true,
    ]
);
// ajouter dans TWIG une fonction 'url'
$twig->addFunction(
    new \Twig\TwigFunction('url', function ($url) {
        return APP_ROOT_URL_COMPLETE . $url;
    })
);
// ajouter dans TWIG une fonction 'asset'
$twig->addFunction(
    new \Twig\TwigFunction('asset', function ($asset) {
        return APP_ROOT_URL_COMPLETE . '/assets' . $asset;
    })
);


/**  6 */
// traiter les différentes routes de l'application
require 'router.php';
