<?php

namespace App\Model;

class Responsable extends Model
{
    protected $tableName = APP_TABLE_PREFIX . 'formation';
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    public function getFormationInfo($id)
    {
        $sql = "SELECT id_formation, email_resp_stage, nom_resp_stage, prenom_resp_stage, mp_resp_stage
            FROM formation
            WHERE id_formation = :id";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        return $sth->fetch();
    }
    public function getEtudiantsInfos($id)
    {
        $sql = "SELECT e.nom_etudiant, e.prenom_etudiant, e.tel_etudiant, e.email_etudiant, COUNT(se.id_etudiant) AS nombre_entretiens
            FROM etudiant e
            LEFT JOIN souhaiter_entretien se ON e.id_etudiant = se.id_etudiant
            WHERE e.id_formation = :id
            GROUP BY e.nom_etudiant, e.prenom_etudiant, e.tel_etudiant, e.email_etudiant
            ORDER BY nombre_entretiens ASC";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        return $sth->fetchAll();
    }
    public function formationReglages($id)
    {
        $sql = "SELECT DATE_FORMAT(date_deb_insc, '%Y-%m-%d') AS date_deb_insc, DATE_FORMAT(date_fin_insc, '%Y-%m-%d') AS date_fin_insc, nb_max_entretiens
                FROM formation
                WHERE id_formation = :id";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        return $sth->fetch();
    }
    public function updateFormationReglages($id, $nouvelle_date_deb_insc, $nouvelle_date_fin_insc, $nouveau_nb_max_entretiens)
    {
        $sql = "UPDATE formation
            SET date_deb_insc = :nouvelle_date_deb_insc,
                date_fin_insc = :nouvelle_date_fin_insc,
                nb_max_entretiens = :nouveau_nb_max_entretiens
            WHERE id_formation = :id";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->bindParam(':nouvelle_date_deb_insc', $nouvelle_date_deb_insc);
        $sth->bindParam(':nouvelle_date_fin_insc', $nouvelle_date_fin_insc);
        $sth->bindParam(':nouveau_nb_max_entretiens', $nouveau_nb_max_entretiens);

        return $sth->execute();
    }
}
