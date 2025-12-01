<?php

include_once '../init.php';

use MongoDB\BSON\ObjectId;

$manager = getMongoDbManager();
$collection = $manager->selectCollection('tp');

if (!empty($_GET['id'])) {
    $id = trim($_GET['id']);

    try {
        $result = $collection->deleteOne(['_id' => new ObjectId($id)]);

        if ($result->getDeletedCount() > 0) {
            header('Location: /index.php');
            exit;
        } else {
            echo "Aucun document trouvé avec cet identifiant.";
        }
    } catch (Exception $e) {
        echo "Erreur lors de la suppression : " . $e->getMessage();
    }

} else {
    echo "Aucun identifiant fourni.";
}
