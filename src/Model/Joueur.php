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

    public static function checkFirstConnexion($idJoueur)
    {
        // 1. Trouver le cadavre en cours
        $cadavreInProgress = self::isCadavreInProgress();

        if (!$cadavreInProgress) {
            // Pas de cadavre en cours
            return false;
        }

        $cadavreId = $cadavreInProgress[0]['id_cadavre'];

        // 2. Obtenir les contributions associées à ce cadavre
        $contributionsForCadavre = self::getContributionsForCadavre($cadavreId);

        // 3. Sélectionner une contribution aléatoire parmi les contributions disponibles
        $randomContribution = self::selectRandomContribution($contributionsForCadavre);

        // 4. Vérifier si le joueur a déjà reçu cette contribution
        if (!self::hasPlayerReceivedContribution($idJoueur, $randomContribution['id_contribution'])) {
            // 5. Insérer la contribution aléatoire sélectionnée pour le joueur
            self::assignRandomContributionToPlayer($idJoueur, $randomContribution);

            return self::getContributionByPlayer($idJoueur);
        } else {

            return self::getContributionByPlayer($idJoueur);
        }
    }

    public static function getContributionsForCadavre($cadavreId)
    {
        $sql = "SELECT id_contribution, texte_contribution, id_cadavre FROM contribution WHERE id_cadavre = :cadavreId";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':cadavreId', $cadavreId);
        $sth->execute();

        return $sth->fetchAll();
    }

    public static function selectRandomContribution($contributions)
    {
        // Sélectionnez une contribution aléatoire parmi les contributions disponibles
        $randomIndex = array_rand($contributions);
        return $contributions[$randomIndex];
    }

    public static function hasPlayerReceivedContribution($joueurId, $contributionId)
    {
        $sql = "SELECT COUNT(*) as count FROM contribution_aléatoiree WHERE id_joueur = :joueurId AND num_contribution = :contributionId";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':joueurId', $joueurId);
        $sth->bindParam(':contributionId', $contributionId);
        $sth->execute();

        $result = $sth->fetch();

        return $result['count'] > 0;
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
        $sql = "SELECT c.*, ca.id_cadavre
        FROM contribution_aléatoiree ca
        JOIN contribution c ON ca.num_contribution = c.id_contribution
        JOIN cadavre cad ON c.id_cadavre = cad.id_cadavre
        WHERE ca.id_joueur = :joueurId
        AND cad.date_debut_cadavre <= NOW()
        AND cad.date_fin_cadavre >= NOW()
        LIMIT 1";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':joueurId', $joueurId);
        $sth->execute();

        return $sth->fetch();
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

            // Insérer la contribution dans la table contribution
            $sql = "INSERT INTO contribution (texte_contribution, date_soumission, ordre_soumission, id_joueur, id_cadavre)
                VALUES (:texte_contribution, NOW(), :nouvel_ordre, :joueurId, :cadavreId)";
            $sth = self::$dbh->prepare($sql);
            $sth->bindParam(':texte_contribution', $texteContribution);
            $sth->bindParam(':nouvel_ordre', $nouvelOrdre);
            $sth->bindParam(':joueurId', $joueurId);
            $sth->bindParam(':cadavreId', $cadavreId);
            $sth->execute();

            // Mettre à jour le nombre de contributions dans la table cadavre
            $sql = "UPDATE cadavre SET nb_contributions = nb_contributions + 1 WHERE id_cadavre = :cadavreId";
            $sth = self::$dbh->prepare($sql);
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