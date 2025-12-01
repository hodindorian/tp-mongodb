<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$collection = $manager->selectCollection('tp');

if (!empty($_POST)) {
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $century = isset($_POST['century']) ? trim($_POST['century']) : '';

    if ($id !== '' && ($title !== '' || $author !== '' || $century !== '')) {
        try {
            $result = $collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => [
                    'titre' => $title,
                    'auteur' => $author,
                    'siecle' => $century
                ]]
            );

            if ($result->getModifiedCount() > 0) {
                header('Location: /index.php');
                exit;
            } else {
                $message = "Aucune modification effectuée (valeurs identiques ou document introuvable).";
            }
        } catch (Exception $e) {
            $message = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    } else {
        $message = "Champs invalides.";
    }

    try {
        echo $twig->render('update.html.twig', [
            'message' => $message ?? null,
            'entity' => [
                '_id' => $id,
                'titre' => $title,
                'auteur' => $author,
                'siecle' => $century
            ]
        ]);
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }

} elseif (!empty($_GET['id'])) {
    $id = trim($_GET['id']);

    try {
        $entity = $collection->findOne(['_id' => new ObjectId($id)]);
        if (!$entity) {
            echo "Livre introuvable.";
            exit;
        }

        echo $twig->render('update.html.twig', [
            'entity' => $entity
        ]);
    } catch (Exception $e) {
        echo "Erreur lors du chargement du livre : " . $e->getMessage();
    }

} else {
    echo "Aucun identifiant fourni.";
}
