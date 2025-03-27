<?php
/************************************************************
 * session_check.php
 * Enthält Funktionen, um den Loginstatus und die Rolle zu prüfen
 ************************************************************/

function isLoggedIn()
{
    return !empty($_SESSION['user_id']);
}

function isAdmin()
{
    return (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
}
