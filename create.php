<?php
require_once 'db.php';
require_once 'session_check.php';

/************************************************************
 * create.php
 * Ermöglicht dem Admin, einen Film in die DB zu legen
 ************************************************************/

if (!isLoggedIn() || !isAdmin()) {
    exit("Nur Admin darf Filme erstellen. <a href='index.php'>Zurück</a>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titel     = trim($_POST['titel']);
    $genre     = trim($_POST['genre']);
    $regisseur = trim($_POST['regisseur']);
    $bewertung = (float)($_POST['bewertung'] ?? 0);

    if ($titel) {
        bulkWrite('movies', [
            ['type' => 'insert', 'data' => [
                'titel'     => $titel,
                'genre'     => $genre,
                'regisseur' => $regisseur,
                'bewertung' => $bewertung
            ]]
        ]);
        header("Location: index.php?role=admin");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Film hinzufügen</title>
</head>
<body>
<h2>Neuen Film hinzufügen</h2>
<form method="post" action="create.php">
    <div>
        <label>Titel:</label>
        <input type="text" name="titel" required>
    </div>
    <div>
        <label>Genre:</label>
        <input type="text" name="genre">
    </div>
    <div>
        <label>Regisseur:</label>
        <input type="text" name="regisseur">
    </div>
    <div>
        <label>Bewertung:</label>
        <input type="number" step="0.1" name="bewertung">
    </div>
    <button type="submit">Speichern</button>
</form>
<p><a href="index.php?role=admin">Zurück zur Übersicht</a></p>
</body>
</html>
