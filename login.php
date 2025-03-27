<?php
require_once 'db.php';
require_once 'session_check.php';

/************************************************************
 * login.php
 * Loggt den Nutzer ein und speichert user_id + role in die Session
 ************************************************************/

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username && $password) {
        // Nutzer in DB suchen
        $userCursor = getCollection('users', ['username' => $username]);
        $userData   = current($userCursor->toArray()) ?? null;

        // Passwort prüfen
        if ($userData && password_verify($password, $userData->password)) {
            $_SESSION['user_id']  = (string)$userData->_id;
            $_SESSION['username'] = $userData->username;
            $_SESSION['role']     = $userData->role;
            header("Location: index.php");
            exit;
        } else {
            echo "<p>Login fehlgeschlagen. <a href='login.php'>Nochmal versuchen</a></p>";
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
<?php if (!isLoggedIn()): ?>
    <h2>Login</h2>
    <form method="post" action="login.php">
        <input type="hidden" name="action" value="login">
        <div>
            <label>Nutzername:</label>
            <input type="text" name="username" required>
        </div>
        <div>
            <label>Passwort:</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Einloggen</button>
    </form>
    <p>Noch keinen Account? <a href="register_form.php">Jetzt registrieren</a></p>
<?php else: ?>
    <p>Du bist bereits eingeloggt. <a href="index.php">Weiter zur Startseite</a></p>
<?php endif; ?>
</body>
</html>
