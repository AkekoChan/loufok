<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

session_start();

use App\Helper\HTTP;
use App\Model\Administrateur;

class AdministrateurController extends Controller
{
    public function index($id)
    {
        if (isset($_SESSION['role'])) {
            $role = $_SESSION['role'];

            if ($role === 'joueur') {
                HTTP::redirect("/joueur/{$id}");
            } else {
                $admin = Administrateur::getInstance()->getAdmin($id);

                $checkCadavreStatus = Administrateur::getInstance()->checkCadavreStatus();

                if ($checkCadavreStatus === true) {
                    $errorMessage = "IL Y A DEJA UN CADAVRE PREVU OU EN COURS... MERCI DE REVENIR PLUS TARD !";
                }

                $this->display(
                    'administrateur/dashboard.html.twig',
                    [
                        'admin' => $admin,
                        'errorMessage' => $errorMessage ?? null
                    ]
                );
            }
        } else {
            HTTP::redirect('/');
        }
    }

    public function add($idAdministrateur)
    {
        $title = trim($_POST['title']);
        $firstContribution = trim($_POST['first-contribution']);
        $startDate = $_POST['start-date'];
        $endDate = $_POST['end-date'];
        $idAdmin = $idAdministrateur;

        $errors = [];

        switch (true) {
            case empty($title):
                array_push($errors, "LE CHAMP TITRE EST REQUIS !");
                break;

            case empty($startDate):
                array_push($errors, "LE CHAMP DATE DE DEBUT EST REQUIS !");
                break;

            case empty($endDate):
                array_push($errors, "LE CHAMP DATE DE FIN EST REQUIS !");
                break;

            case strlen($title) > 100:
                array_push($errors, "VOTRE TITRE EST TROP LONG !");
                break;

            case strlen($firstContribution) < 50 || strlen($firstContribution) > 280:
                array_push($errors, "VOTRE CONTRIBUTION DOIT COMPRENDRE ENTRE 50 ET 280 CARACTERES !");
                break;

            case empty($startDate) || strtotime($startDate) >= time():
                array_push($errors, "VOTRE DATE DE DEBUT DE CADAVRE EST INVALIDE !");
                break;

            case empty($endDate) || strtotime($endDate) <= strtotime($startDate):
                array_push($errors, "VOTRE DATE DE FIN DE CADAVRE EST INVALIDE !");
                break;
        }

        if (empty($errors)) {
            Administrateur::getInstance()->addCadavre($idAdmin, $title, $firstContribution, $startDate, $endDate);

            HTTP::redirect("/administrateur/{$idAdmin}");
        } else {
            $this->display('administrateur/error_cadavre.html.twig', [
                'errors' => $errors,
                'idAdmin' => $idAdmin,
            ]);
        }
    }
}
