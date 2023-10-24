<?php

declare(strict_types=1);

namespace App\Controller;

session_start();

use App\Helper\HTTP;
use App\Model\Joueur;

class JoueurController extends Controller
{
    public function index($id)
    {

        if (isset($_SESSION['role'])) {
            $role = $_SESSION['role'];

            if ($role === 'administrateur') {
                HTTP::redirect("/administrateur/{$id}");
            } else {
                $cadavreInProgress = Joueur::getInstance()->isCadavreInProgress();
                $player = Joueur::getInstance()->getPlayer($id);

                if ($cadavreInProgress) {
                    $contribution = Joueur::getInstance()->checkFirstConnexion($id);
                    $cadavreContributions = $cadavreInProgress;
                    $hasPlayed = Joueur::getInstance()->hasPlayerContributedToCadavre($id, $cadavreContributions[0]["id_cadavre"]);
                    $errorMessage = null;
                } else {
                    $contribution = null;
                    $cadavreContributions = null;
                    $hasPlayed = null;
                    $errorMessage = "IL N'Y A RIEN À VOIR ICI ! AUCUNE FABRICATION DE CADAVRE EN COURS...";
                }

                $this->display(
                    'joueur/cadavre.html.twig',
                    [
                        'player' => $player,
                        'contribution' => $contribution,
                        'cadavreContributions' => $cadavreContributions,
                        'hasPlayed' => $hasPlayed,
                        'errorMessage' => $errorMessage,
                    ]
                );
            }
        } else {
            HTTP::redirect('/');
        }
    }

    public function add($idJoueur)
    {
        $idJoueur = $_SESSION['user_id'];
        $newContribution = trim($_POST['new_contribution']);

        if (strlen($newContribution) < 50 || strlen($newContribution) > 240) {
            HTTP::redirect("/error");
        } else {
            Joueur::getInstance()->addContribution($idJoueur, $newContribution);
            HTTP::redirect("/joueur/{$idJoueur}");
        }
    }

    public function error()
    {
        $this->display('joueur/error_contribution.html.twig', [
            'idJoueur' => $_SESSION['user_id'],
        ]);
    }

    public function old($idJoueur)
    {
        $player = Joueur::getInstance()->getPlayer($idJoueur);
        $data = Joueur::getInstance()->getOldCadavreWithContributions($idJoueur);


        $this->display('joueur/old_cadavre.html.twig', [
            'data' => $data,
            'player' => $player
        ]);
    }
}
