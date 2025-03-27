<?php
require_once 'db.php';
require_once 'session_check.php';

/************************************************************
 * register.php
 * Registriert einen neuen Nutzer in der Datenbank
 ************************************************************/

if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role     = 'user'; // Standardrolle

    if ($username && $password) {
        // Prüfen, ob der Nutzername bereits vergeben ist
        $existing      = getCollection('users', ['username' => $username]);
        $alreadyExists = false;
        foreach ($existing as $ex) {
            $alreadyExists = true;
            break;
        }
        if ($alreadyExists) {
            echo "<p>Benutzername ist schon vergeben. <a href='register_form.php'>Nochmal versuchen</a></p>";
        } else {
            // Datensatz einfügen
            bulkWrite('users', [
                ['type' => 'insert', 'data' => [
                    'username' => $username,
                    'password' => $password,
                    'role'     => $role
                ]]
            ]);
            echo "<p>Registrierung erfolgreich. <a href='login.php'>Hier einloggen</a>.</p>";
        }
    }
    exit;
}
