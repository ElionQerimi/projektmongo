<?php
require_once 'db.php';
require_once 'session_check.php';

/**********************************************************************
 * edit_film.php
 *
 * - Separate Seite zum Bearbeiten eines Films (alle Felder).
 * - Nur Admin darf öffnen.
 **********************************************************************/

/**
 * Film-Update
 */
function updateFilm($filmId, $data)
{
    bulkWrite('movies', [
        [
            'type'   => 'update',
            'filter' => ['_id' => new MongoDB\BSON\ObjectId($filmId)],
            'data'   => $data
        ]
    ]);
}

/**
 * Einzelnen Film laden
 */
function getFilmById($id)
{
    $filter = ['_id' => new MongoDB\BSON\ObjectId($id)];
    $cursor = getCollection('movies', $filter);
    $arr    = $cursor->toArray();
    return (count($arr) > 0) ? $arr[0] : null;
}

if (!isLoggedIn() || !isAdmin()) {
    exit("Nur Admin darf Filme bearbeiten. <a href='index.php'>Zurück</a>");
}

// Film-ID aus GET
$filmId = $_GET['film'] ?? null;
if (!$filmId) {
    exit("Keine Film-ID angegeben. <a href='index.php'>Zurück</a>");
}

// Update-Formular
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateFilm') {
    $titel     = trim($_POST['titel']);
    $genre     = trim($_POST['genre']);
    $regisseur = trim($_POST['regisseur']);
    $bewertung = (float)($_POST['bewertung']);

    updateFilm($filmId, [
        'titel'     => $titel,
        'genre'     => $genre,
        'regisseur' => $regisseur,
        'bewertung' => $bewertung
    ]);
    header("Location: index.php?film=" . urlencode($filmId));
    exit;
}

// Film laden
$film = getFilmById($filmId);
if (!$film) {
    exit("Film nicht gefunden. <a href='index.php'>Zurück</a>");
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Film bearbeiten</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f3f4f6; color: #333;
            margin: 0; padding: 0;
        }
        .container {
            max-width: 600px; margin: 50px auto; padding: 20px; background:#fff;
            border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);
        }
        h2 {
            margin-bottom:15px;
        }
        label {
            display:block; margin:10px 0 5px; font-weight:bold;
        }
        input[type="text"], input[type="number"] {
            width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;
        }
        button {
            background:#28a745; color:#fff; border:none; padding:8px 16px; border-radius:6px;
            cursor:pointer; font-weight:bold; margin-top:15px;
        }
        button:hover {
            background:#218838;
        }
        a {
            text-decoration:none; color:#007bff;
        }
        a:hover {
            color:#0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Film bearbeiten</h2>
    <form method="post">
        <input type="hidden" name="action" value="updateFilm">

        <label for="titel">Titel:</label>
        <input type="text" id="titel" name="titel" value="<?= htmlspecialchars($film->titel) ?>" required>

        <label for="genre">Genre:</label>
        <input type="text" id="genre" name="genre" value="<?= htmlspecialchars($film->genre) ?>">

        <label for="regisseur">Regisseur:</label>
        <input type="text" id="regisseur" name="regisseur" value="<?= htmlspecialchars($film->regisseur) ?>">

        <label for="bewertung">Bewertung:</label>
        <input type="number" step="0.1" id="bewertung" name="bewertung" value="<?= $film->bewertung ?>">

        <button type="submit">Speichern</button>
    </form>
    <p style="margin-top:20px;"><a href="index.php?film=<?= urlencode($filmId) ?>">← Zurück zum Film</a></p>
</div>
</body>
</html>
