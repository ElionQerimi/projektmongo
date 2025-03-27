<?php
require_once 'db.php';
require_once 'session_check.php';

/************************************************************
 * edit.php
 * Ermöglicht Admin, ein Feld eines Films oder einer Bewertung zu bearbeiten
 ************************************************************/

if (!isLoggedIn() || !isAdmin()) {
    exit("Nur Admin darf bearbeiten. <a href='index.php'>Zurück</a>");
}

$type  = $_GET['type']  ?? null;    // 'movie' oder 'review'
$id    = $_GET['id']    ?? null;
$field = $_GET['field'] ?? null;
$value = $_GET['value'] ?? null;

if ($type && $id && $field !== null && $value !== null) {
    $collection = ($type === 'movie') ? 'movies' : 'reviews';

    // Datentyp korrigieren (z.B. Bewertung als float)
    if ($field === 'bewertung') {
        $value = (float)$value;
    }

    // Update ausführen
    bulkWrite($collection, [
        [
            'type'   => 'update',
            'filter' => ['_id' => new MongoDB\BSON\ObjectId($id)],
            'data'   => [$field => $value]
        ]
    ]);
}
header("Location: index.php?role=admin");
exit;
