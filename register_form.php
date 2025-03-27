<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrierung</title>
</head>
<body>
    <h2>Registrierung</h2>
    <form method="post" action="register.php">
        <input type="hidden" name="action" value="register">
        <div>
            <label>Nutzername:</label>
            <input type="text" name="username" required>
        </div>
        <div>
            <label>Passwort:</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Registrieren</button>
    </form>
    <p>Schon registriert? <a href="login.php">Jetzt einloggen</a></p>
</body>
</html>
