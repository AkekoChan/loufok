<?php

declare(strict_types=1);

namespace App\Controller;

session_start();

use App\Helper\HTTP;
use App\Model\Joueur;

class JoueurController extends Controller
{
    /**
     * Affiche la page du joueur actuel, gère les connexions initiales et les contributions.
     *
     * @param [type] $id
     * @return void
     */
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
                    $cadavreContributions = $cadavreInProgress;
                    $contribution = Joueur::getInstance()->checkFirstConnexion($id);
                    $hasPlayed = Joueur::getInstance()->hasPlayerContributedToCadavre($id, $cadavreContributions[0]["id_cadavre"]);
                } else {
                    $errorMessage = "IL N'Y A RIEN À VOIR ICI ! AUCUNE FABRICATION DE CADAVRE EN COURS...";
                }

                $this->display(
                    'joueur/cadavre.html.twig',
                    [
                        'player' => $player,
                        'contribution' => $contribution ?? null,
                        'cadavreContributions' => $cadavreContributions ?? null,
                        'hasPlayed' => $hasPlayed ?? null,
                        'errorMessage' => $errorMessage ?? null,
                    ]
                );
            }
        } else {
            HTTP::redirect('/');
        }
    }

    /**
     * Gère l'ajout d'une nouvelle contribution par le joueur.
     *
     * @param [type] $idJoueur
     * @return void
     */
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

    /**
     * Affiche une page d'erreur en cas de contribution incorrecte.
     *
     * @return void
     */
    public function error()
    {
        $this->display('joueur/error_contribution.html.twig', [
            'idJoueur' => $_SESSION['user_id'],
        ]);
    }

    /**
     * Affiche les anciennes participations du joueur.
     *
     * @param [type] $idJoueur
     * @return void
     */
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
