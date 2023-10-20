<?php

namespace App\Model;

class Etudiant extends Model
{
    protected $tableName = APP_TABLE_PREFIX . 'etudiant';

    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function etudiantData($id)
    {
        $sql = "SELECT id_etudiant, nom_etudiant, prenom_etudiant, tel_etudiant, email_etudiant
            FROM etudiant
            WHERE id_etudiant = :id";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        return $sth->fetch();
    }

    public function sesEntretiens($id)
    {
        $sql = "SELECT e.id_entreprise, e.nom_entreprise, e.dpt_entreprise, o.fichier_offre
            FROM souhaiter_entretien s
            INNER JOIN entreprise e ON s.id_entreprise = e.id_entreprise
            LEFT JOIN offre o ON e.id_entreprise = o.id_entreprise
            WHERE s.id_etudiant = :id";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        $entreprises = [];
        while ($row = $sth->fetch()) {
            $id_entreprise = $row['id_entreprise'];
            if (!isset($entreprises[$id_entreprise])) {
                $entreprises[$id_entreprise] = [
                    'id_entreprise' => $row['id_entreprise'],
                    'nom_entreprise' => $row['nom_entreprise'],
                    'dpt_entreprise' => $row['dpt_entreprise'],
                    'fichiers_offre' => [],
                ];
            }
            if ($row['fichier_offre']) {
                $entreprises[$id_entreprise]['fichiers_offre'][] = $row['fichier_offre'];
            }
        }
        return $entreprises;
    }

    public function potentielEntretiens($id)
    {
        $sql = "SELECT e.id_entreprise, e.nom_entreprise, e.dpt_entreprise, o.fichier_offre
            FROM entreprise e
            LEFT JOIN offre o ON e.id_entreprise = o.id_entreprise
            WHERE e.id_entreprise NOT IN (
                SELECT se.id_entreprise
                FROM souhaiter_entretien se
                WHERE se.id_etudiant = :id
            )
            AND e.id_entreprise IN (
                SELECT fo.id_entreprise
                FROM etudiant fe
                INNER JOIN offre fo ON fe.id_formation = fo.id_formation
                WHERE fe.id_etudiant = :id
            )";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        $entreprises = [];
        while ($row = $sth->fetch()) {
            $id_entreprise = $row['id_entreprise'];
            if (!isset($entreprises[$id_entreprise])) {
                $entreprises[$id_entreprise] = [
                    'id_entreprise' => $row['id_entreprise'],
                    'nom_entreprise' => $row['nom_entreprise'],
                    'dpt_entreprise' => $row['dpt_entreprise'],
                    'fichiers_offre' => [],
                ];
            }
            if ($row['fichier_offre']) {
                $entreprises[$id_entreprise]['fichiers_offre'][] = $row['fichier_offre'];
            }
        }
        return $entreprises;
    }

    public function supprimerEntretien($idEntreprise, $idEtudiant)
    {
        $sql = "DELETE FROM souhaiter_entretien WHERE id_entreprise = :idEntreprise AND id_etudiant = :idEtudiant";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':idEntreprise', $idEntreprise);
        $sth->bindParam(':idEtudiant', $idEtudiant);
        $sth->execute();
    }

    public function ajouterEntretien($idEntreprise, $idEtudiant)
    {
        $sql = "INSERT INTO souhaiter_entretien (id_entreprise, id_etudiant) VALUES (:idEntreprise, :idEtudiant)";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':idEntreprise', $idEntreprise);
        $sth->bindParam(':idEtudiant', $idEtudiant);
        $sth->execute();
    }
    public function getDateParameters($id)
    {
        $sql = "SELECT DATE_FORMAT(f.date_deb_insc, '%Y-%m-%d') AS date_deb_insc, DATE_FORMAT(f.date_fin_insc, '%Y-%m-%d') AS date_fin_insc, f.nb_max_entretiens
                FROM formation f
                INNER JOIN etudiant e ON f.id_formation = e.id_formation
                WHERE e.id_etudiant = :id";

        $sth = self::$dbh->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();

        return $sth->fetch();
    }
}
