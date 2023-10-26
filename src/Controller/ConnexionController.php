<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

session_start();

use App\Helper\HTTP;
use App\Model\Connexion;

class ConnexionController extends Controller
{

    /**
     * Affiche la page de connexion et gère l'authentification de l'utilisateur.
     *
     * @return void
     */
    public function index()
    {
        $errorMessage = null;
        $_SESSION['role'] = NULL;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['mdp'];

            $user = Connexion::getInstance()->authenticateUser($email, $password);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];

                if ($user['type'] === 'joueur') {
                    $_SESSION['role'] = 'joueur';
                    HTTP::redirect("/joueur/{$user['id']}");
                } elseif ($user['type'] === 'administrateur') {
                    $_SESSION['role'] = 'administrateur';
                    HTTP::redirect("/administrateur/{$user['id']}");
                }
            } else {
                $errorMessage = "Adresse mail ou mot de passe incorrect.";
            }
        }
        $this->display(
            'connexion/index.html.twig',
            ['errorMessage' => $errorMessage ?? null]
        );
    }

    /**
     * Déconnecte l'utilisateur en réinitialisant les sessions et le redirige vers la page d'accueil.
     *
     * @return void
     */
    public function disconnect()
    {
        $_SESSION['role'] = NULL;
        $_SESSION['user_id'] = NULL;

        HTTP::redirect("/");
    }
}
