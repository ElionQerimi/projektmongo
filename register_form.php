<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrierung</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <form method="post" action="register.php">
            <h2>Registrierung</h2>
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
            <p>Schon registriert? <a href="login.php">Jetzt einloggen</a></p>
        </form>
    </div>
</body>
