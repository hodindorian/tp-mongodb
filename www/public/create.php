<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

if (!empty($_POST)) {
    $title = isset($_POST['titre']) ? trim($_POST['titre']) : '';
    $author = isset($_POST['auteur']) ? trim($_POST['auteur']) : '';
    $siecle = isset($_POST['siecle']) ? trim($_POST['siecle']) : '';

    if($title!='' || $author!='' || $siecle!=''){
        $book = [
            'titre' => $title,
            'auteur' => $author,
            'siecle' => $siecle,
        ];
        $manager->selectCollection('tp')->insertOne($book);
        header('Location: /index.php');
        exit;

    }
} else {
    try {
        echo $twig->render('create.html.twig');
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
}

