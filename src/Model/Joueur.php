<?php

namespace App\Model;

use App\Helper\HTTP;

class Joueur extends Model
{
    protected $tableName = APP_TABLE_PREFIX . 'joueur';

    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getPlayer($id)
    {
        $sql = "SELECT id_joueur, ad_mail_joueur, sexe, ddn, nom_plume
        FROM joueur
        WHERE id_joueur = :id";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        return $sth->fetch();
    }

    public static function checkFirstConnexion($id)
    {
        $sql = "SELECT COUNT(*) as count FROM contribution_aléatoiree WHERE id_joueur = :joueurId";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':joueurId', $id);
        $sth->execute();
        $result = $sth->fetch();

        echo "Résultat de la requête : " . $result['count']; // Affiche le résultat de la requête

        if ($result['count'] == '0') {
            echo "C'est la première connexion du joueur.";
            $randomContribution = self::getRandomContribution();

            self::assignRandomContributionToPlayer($id, $randomContribution);

            return self::getContributionByPlayer($id);
        } else {
            echo "Le joueur a déjà des contributions.";

            return self::getContributionByPlayer($id);
        }
    }

    public static function getRandomContribution()
    {
        $sql = "SELECT * FROM contribution ORDER BY RAND() LIMIT 1";
        $sth = self::$dbh->prepare($sql);
        $sth->execute();

        return $sth->fetch();
    }

    public static function assignRandomContributionToPlayer($joueurId, $contribution)
    {
        $sql = "INSERT INTO contribution_aléatoiree (id_joueur, id_cadavre, num_contribution) VALUES (:joueurId, :id_cadavre, :num_contribution)";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':joueurId', $joueurId);
        $sth->bindParam(':id_cadavre', $contribution['id_cadavre']);
        $sth->bindParam(':num_contribution', $contribution['id_contribution']);
        $sth->execute();
    }

    public static function getContributionByPlayer($joueurId)
    {
        $sql = "SELECT * FROM contribution_aléatoiree WHERE id_joueur = :joueurId";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':joueurId', $joueurId);
        $sth->execute();

        $result = $sth->fetch();

        if ($result) {
            // Si le joueur a une contribution, récupérez les informations de la contribution
            $contributionNum = $result['num_contribution'];
            $idCadavre = $result['id_cadavre'];

            $sql = "SELECT * FROM contribution WHERE id_contribution = :contributionNum AND id_cadavre = :idCadavre";
            $sth = self::$dbh->prepare($sql);
            $sth->bindParam(':contributionNum', $contributionNum);
            $sth->bindParam(':idCadavre', $idCadavre);
            $sth->execute();

            return $sth->fetch();
        }
    }

    public static function isCadavreInProgress()
    {
        $sql = "SELECT cadavre.*, contribution.* FROM cadavre
        LEFT JOIN contribution ON cadavre.id_cadavre = contribution.id_cadavre
        WHERE cadavre.date_debut_cadavre <= NOW() AND cadavre.date_fin_cadavre >= NOW()";

        $sth = self::$dbh->prepare($sql);
        $sth->execute();
        $cadavreInProgress = $sth->fetchAll();

        if ($cadavreInProgress) {
            // Le cadavre est en cours, retournez-le avec ses contributions
            return $cadavreInProgress;
        } else {
            return false;
        }
    }

    public static function hasPlayerContributedToCadavre($joueurId, $cadavreId)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM contribution
                WHERE id_joueur = :joueurId
                AND id_cadavre = :cadavreId";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':joueurId', $joueurId);
        $sth->bindParam(':cadavreId', $cadavreId);
        $sth->execute();

        $result = $sth->fetch();

        if ($result['count'] > 0) {
            return true;
        } else {
            return false;
        }
    }


    public static function getOrder()
    {
        $sql = "SELECT MAX(ordre_soumission) AS last_order, ca.id_cadavre
        FROM contribution AS c
        JOIN cadavre AS ca ON c.id_cadavre = ca.id_cadavre
        WHERE ca.date_debut_cadavre <= CURRENT_DATE() AND ca.date_fin_cadavre >= CURRENT_DATE()";
        $sth = self::$dbh->prepare($sql);
        $sth->execute();

        return $sth->fetchAll();
    }
    public static function addContribution($joueurId, $texteContribution)
    {
        $order = self::getOrder();

        if (!empty($order) && isset($order[0]['last_order']) && isset($order[0]['id_cadavre'])) {
            $nouvelOrdre = $order[0]['last_order'] + 1;
            $cadavreId = $order[0]['id_cadavre'];

            $sql = "INSERT INTO contribution (texte_contribution, date_soumission, ordre_soumission, id_joueur, id_cadavre)
                VALUES (:texte_contribution, NOW(), :nouvel_ordre, :joueurId, :cadavreId)";
            $sth = self::$dbh->prepare($sql);
            $sth->bindParam(':texte_contribution', $texteContribution);
            $sth->bindParam(':nouvel_ordre', $nouvelOrdre);
            $sth->bindParam(':joueurId', $joueurId);
            $sth->bindParam(':cadavreId', $cadavreId);
            $sth->execute();
        }
    }
    public static function getContributionsOfOldCadavre($idCadavre)
    {
        $sql = "(
            SELECT j.id_joueur, j.nom_plume, c.texte_contribution, c.ordre_soumission
            FROM contribution c
            JOIN joueur j ON c.id_joueur = j.id_joueur
            WHERE c.id_cadavre = :idCadavre
            
            UNION ALL
            
            SELECT a.id_administrateur AS id_joueur, a.nom_plume AS nom_plume, c.texte_contribution, c.ordre_soumission
            FROM contribution c
            JOIN administrateur a ON c.id_administrateur = a.id_administrateur
            WHERE c.id_cadavre = :idCadavre
          ) 
          ORDER BY ordre_soumission";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam("idCadavre", $idCadavre);
        $sth->execute();

        return $sth->fetchAll();
    }

    public static function getOldCadavreWithContributions($id)
    {
        $sql = 'SELECT a.*
                FROM cadavre a
                JOIN contribution c ON a.id_cadavre = c.id_cadavre
                WHERE c.id_joueur = :idJoueur
                AND  a.date_fin_cadavre < NOW()
                ORDER BY a.date_debut_cadavre DESC
                LIMIT 1';
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam('idJoueur', $id);
        $sth->execute();

        $cadavre = $sth->fetch();
        $contributions = self::getContributionsOfOldCadavre($cadavre['id_cadavre']);

        $data = array(
            'cadavre' => $cadavre,
            'contributions' => $contributions
        );

        return $data;
    }
}