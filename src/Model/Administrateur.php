<?php

namespace App\Model;

use App\Helper\HTTP;

class Administrateur extends Model
{
    protected $tableName = APP_TABLE_PREFIX . 'administrateur';

    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getAdmin($id)
    {
        $sql = "SELECT id_administrateur, ad_mail_administrateur, nom_plume
        FROM administrateur
        WHERE id_administrateur = :id";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        return $sth->fetch();
    }

    public static function checkCadavreStatus()
    {
        $sql = 'SELECT COUNT(*) as count
        FROM cadavre 
        JOIN administrateur ON cadavre.id_administrateur = administrateur.id_administrateur 
        WHERE (cadavre.date_debut_cadavre <= NOW() AND cadavre.date_fin_cadavre >= NOW()) OR cadavre.date_debut_cadavre > NOW()';
        $sth = self::$dbh->prepare($sql);
        $sth->execute();
        $result = $sth->fetch();

        if ($result['count'] == '1') {
            return true;
        } else {
            return false;
        }
    }

    public static function addCadavre($idAdmin, $title, $firstContribution, $startDate, $endDate)
    {

        $sql = "INSERT INTO cadavre (titre_cadavre, date_debut_cadavre, date_fin_cadavre, nb_contributions, id_administrateur) VALUES (:title, :startDate, :endDate, 1, :idAdmin)";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':title', $title);
        $sth->bindParam(':startDate', $startDate);
        $sth->bindParam(':endDate', $endDate);
        $sth->bindParam(':idAdmin', $idAdmin);
        $sth->execute();

        $cadavreId = self::$dbh->lastInsertId();

        $sql = "INSERT INTO contribution (texte_contribution, date_soumission, ordre_soumission, id_administrateur, id_cadavre)
              VALUES (:firstContribution, NOW(), 1, :idAdmin, :cadavreId)";
        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':firstContribution', $firstContribution);
        $sth->bindParam(':idAdmin', $idAdmin);
        $sth->bindParam(':cadavreId', $cadavreId);
        $sth->execute();
    }
}
