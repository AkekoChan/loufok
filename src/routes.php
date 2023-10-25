<?php

declare(strict_types=1);
/*
-------------------------------------------------------------------------------
les routes
-------------------------------------------------------------------------------
 */

return [

  ['GET', '/', 'connexion@index'],
  ['POST', '/connexion', 'connexion@index'],
  ['GET', '/disconnect', 'connexion@disconnect', 'disconnect'],

  ['GET', '/joueur/{id}', 'joueur@index'],
  ['POST', '/joueur/add/{idJoueur}', 'joueur@add'],
  ['GET', '/error', 'joueur@error'],
  ['GET', '/cadavre/{idJoueur}/old', "joueur@old"],

  ["GET", "/administrateur/{id}", "administrateur@index"],
  ['POST', '/administrateur/add/{idAdministrateur}', 'administrateur@add'],




];
