<?php
session_start();

/************************************************************
 * db.php
 * Stellt eine Verbindung zur MongoDB her und enthält Hilfsfunktionen
 ************************************************************/

$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");

/**
 * Daten aus einer Collection abfragen
 */
function getCollection($collection, $filter = [], $options = [])
{
    global $mongo;
    $query  = new MongoDB\Driver\Query($filter, $options);
    return $mongo->executeQuery("FilmBewertungen.$collection", $query);
}

/**
 * Mehrere Operationen (insert, update, delete) in der Collection ausführen
 */
function bulkWrite($collection, $operations)
{
    global $mongo;
    $bulk = new MongoDB\Driver\BulkWrite;
    foreach ($operations as $op) {
        $type   = $op['type'];
        $filter = isset($op['filter']) ? $op['filter'] : [];
        $data   = isset($op['data'])   ? $op['data']   : [];
        switch ($type) {
            case 'insert':
                $bulk->insert($data);
                break;
            case 'update':
                $bulk->update($filter, ['$set' => $data]);
                break;
            case 'delete':
                $bulk->delete($filter);
                break;
        }
    }
    return $mongo->executeBulkWrite("FilmBewertungen.$collection", $bulk);
}
