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

    /**
     * Récupère les informations du joueur.
     *
     * @param [type] $id
     * @return array|false Les informations du joueur ou false en cas d'erreur.
     */
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

    /**
     * Gère la première connexion du joueur, attribue des contributions aléatoires.
     *
     * @param [type] $idJoueur
     * @return array|false Les contributions attribuées au joueur ou false en cas d'erreur.
     */
    public static function checkFirstConnexion($idJoueur)
    {
        $cadavreInProgress = self::isCadavreInProgress();

        if (!$cadavreInProgress) {
            return false;
        } else {
            $cadavreId = $cadavreInProgress[0]['id_cadavre'];

            $contributionsForCadavre = self::getContributionsForCadavre($cadavreId);
            $hasPlayerReceivedContribution = self::hasPlayerReceivedContribution($idJoueur, $cadavreId);

            if (!$hasPlayerReceivedContribution) {
                $randomContribution = self::selectRandomContribution($contributionsForCadavre);

                self::assignRandomContributionToPlayer($idJoueur, $randomContribution);
            }

            return self::getContributionByPlayer($idJoueur);
        }
    }

    /**
     * Récupère les contributions associées à un cadavre donné.
     *
     * @param [type] $cadavreId
     * @return array|false Les contributions du cadavre ou false en cas d'erreur.
     */
    public static function getContributionsForCadavre($cadavreId)
    {
        $sql = "SELECT id_contribution, texte_contribution, id_cadavre FROM contribution WHERE id_cadavre = :cadavreId";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':cadavreId', $cadavreId);
        $sth->execute();

        return $sth->fetchAll();
    }

    /**
     * Sélectionne une contribution aléatoire parmi les contributions disponibles.
     *
     * @param [type] $contributions
     * @return array|false La contribution aléatoire sélectionnée ou false en cas d'erreur.
     */
    public static function selectRandomContribution($contributions)
    {
        $randomIndex = array_rand($contributions);
        return $contributions[$randomIndex];
    }

    /**
     * Vérifie si le joueur a déjà reçu une contribution spécifique.
     *
     * @param [type] $joueurId
     * @param [type] $contributionId
     * @return bool True si le joueur a reçu la contribution, sinon false.
     */
    public static function hasPlayerReceivedContribution($joueurId, $idCadavre)
    {
        $sql = "SELECT COUNT(*) as count FROM contribution_aléatoiree WHERE id_joueur = :joueurId AND id_cadavre = :cadavreId";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':joueurId', $joueurId);
        $sth->bindParam(':cadavreId', $idCadavre);
        $sth->execute();

        $result = $sth->fetch();

        return $result['count'] > 0;
    }

    /**
     * Attribue une contribution aléatoire à un joueur.
     *
     * @param [type] $joueurId
     * @param [type] $contribution
     * @return void
     */
    public static function assignRandomContributionToPlayer($joueurId, $contribution)
    {
        $sql = "INSERT INTO contribution_aléatoiree (id_joueur, id_cadavre, num_contribution) VALUES (:joueurId, :id_cadavre, :num_contribution)";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':joueurId', $joueurId);
        $sth->bindParam(':id_cadavre', $contribution['id_cadavre']);
        $sth->bindParam(':num_contribution', $contribution['id_contribution']);
        $sth->execute();
    }

    /**
     * Récupère la contribution actuelle d'un joueur.
     *
     * @param [type] $joueurId
     * @return array|false La contribution actuelle du joueur ou false en cas d'erreur.
     */
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

    /**
     * Vérifie s'il y a un cadavre en cours.
     *
     * @return array|false Les informations du cadavre en cours ou false s'il n'y a pas de cadavre en cours.
     */
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

    /**
     * Vérifie si le joueur a déjà contribué à un cadavre spécifique.
     *
     * @param [type] $joueurId
     * @param [type] $cadavreId
     * @return bool True si le joueur a déjà contribué au cadavre, sinon false.
     */
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

    /**
     * Récupère l'ordre de soumission actuel.
     *
     * @return array|false Les informations sur l'ordre de soumission ou false en cas d'erreur.
     */
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

    /**
     * Ajoute une nouvelle contribution.
     *
     * @param [type] $joueurId
     * @param [type] $texteContribution
     * @return void
     */
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

            $sql = "UPDATE cadavre SET nb_contributions = nb_contributions + 1 WHERE id_cadavre = :cadavreId";
            $sth = self::$dbh->prepare($sql);
            $sth->bindParam(':cadavreId', $cadavreId);
            $sth->execute();
        }
    }

    /**
     * Récupère les contributions d'un ancien cadavre.
     *
     * @param [type] $idCadavre
     * @return array|false Les contributions de l'ancien cadavre ou false en cas d'erreur.
     */
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

    /**
     * Récupère un ancien cadavre avec ses contributions.
     *
     * @param [type] $id
     * @return array|null Les informations de l'ancien cadavre et ses contributions ou null.
     */
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

        if (!$cadavre) {
            return null;
        }

        $contributions = self::getContributionsOfOldCadavre($cadavre['id_cadavre']);

        $data = array(
            'cadavre' => $cadavre,
            'contributions' => $contributions
        );

        return $data;
    }
}
