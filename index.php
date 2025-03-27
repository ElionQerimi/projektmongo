<?php
require_once 'db.php';
require_once 'session_check.php';

/**********************************************************************
 * index.php
 * 
 * Verbesserte Listenansicht:
 * - Schaltfläche "Bearbeiten" gleich unter "Bewertungen" (in jeder Card)
 * - Paginierung im Format:  ←  Seite X von Y  →
 * - Klick auf Seitenzahl "X" => Eingabe einer neuen Seitenzahl => Sprung
 **********************************************************************/

/**********************************************************************
 * Hilfsfunktionen
 **********************************************************************/

/**
 * Collection abfragen (optional Filter/Sort), mit skip & limit für Pagination.
 */
function fetchCollection($collection, $filter = [], $sort = [], $skip = 0, $limit = 0)
{
    $options = [];
    if (!empty($sort)) {
        $options['sort'] = $sort;
    }
    if ($skip > 0) {
        $options['skip'] = $skip;
    }
    if ($limit > 0) {
        $options['limit'] = $limit;
    }
    return getCollection($collection, $filter, $options);
}

/**
 * Anzahl Dokumente in einer Collection zählen.
 */
function countDocuments($collection, $filter = [])
{
    global $mongo;
    $command = new MongoDB\Driver\Command([
        'count' => $collection,
        'query' => (object)$filter
    ]);
    $res = $mongo->executeCommand("FilmBewertungen", $command)->toArray();
    return $res[0]->n ?? 0;
}

/**
 * Neues Dokument einfügen.
 */
function insertDocument($collection, $data)
{
    bulkWrite($collection, [
        ['type' => 'insert', 'data' => $data]
    ]);
}

/**
 * Dokument (nach _id) löschen.
 */
function deleteDocument($collection, $id)
{
    bulkWrite($collection, [
        [
            'type'   => 'delete',
            'filter' => ['_id' => new MongoDB\BSON\ObjectId($id)]
        ]
    ]);
}

/**
 * Einzelnen Film anhand seiner _id holen (oder null).
 */
function getFilmById($id)
{
    $filter = ['_id' => new MongoDB\BSON\ObjectId($id)];
    $cursor = fetchCollection('movies', $filter);
    $arr    = $cursor->toArray();
    return (count($arr) > 0) ? $arr[0] : null;
}

/**
 * Bewertungen zu einem bestimmten Film holen (Array).
 */
function getReviewsByFilm($filmId)
{
    $filter = ['film_id' => new MongoDB\BSON\ObjectId($filmId)];
    $cursor = fetchCollection('reviews', $filter);
    return $cursor->toArray();
}

/**********************************************************************
 * Aktionen (POST/GET)
 **********************************************************************/

// (Admin) Neuer Film
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addMovie') {
    if (!isLoggedIn() || !isAdmin()) {
        exit("Nur Admin darf Filme hinzufügen.");
    }
    $titel     = trim($_POST['titel']);
    $genre     = trim($_POST['genre']);
    $regisseur = trim($_POST['regisseur']);
    $bewertung = (float)($_POST['bewertung'] ?? 0);
    if ($titel !== '') {
        insertDocument('movies', [
            'titel'     => $titel,
            'genre'     => $genre,
            'regisseur' => $regisseur,
            'bewertung' => $bewertung
        ]);
    }
    header("Location: index.php");
    exit;
}

// (Eingeloggte User) Neue Bewertung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addReview') {
    if (!isLoggedIn()) {
        exit("Nur eingeloggte Nutzer dürfen Bewertungen schreiben.");
    }
    $film_id = $_POST['film_id'] ?? '';
    $rating  = (int)($_POST['rating'] ?? 0);
    $text    = trim($_POST['text'] ?? '');
    if ($film_id && $rating > 0 && $text !== '') {
        insertDocument('reviews', [
            'film_id' => new MongoDB\BSON\ObjectId($film_id),
            'user_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id']),
            'rating'  => $rating,
            'text'    => $text
        ]);
    }
    header("Location: index.php?film=" . urlencode($film_id));
    exit;
}

// (Admin) Film löschen
if (isset($_GET['delete_movie']) && isLoggedIn() && isAdmin()) {
    deleteDocument('movies', $_GET['delete_movie']);
    header("Location: index.php");
    exit;
}

// (Admin) Review löschen
if (isset($_GET['delete_review']) && isLoggedIn() && isAdmin()) {
    $film_id = $_GET['film_id'] ?? '';
    deleteDocument('reviews', $_GET['delete_review']);
    if ($film_id) {
        header("Location: index.php?film=" . urlencode($film_id));
    } else {
        header("Location: index.php");
    }
    exit;
}

/**********************************************************************
 * Layout
 **********************************************************************/
$isAdmin = (isLoggedIn() && isAdmin());
$filmId  = $_GET['film'] ?? null;  // Falls gesetzt: Detailansicht
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Filmportal mit Buttons und einer Pagination: x von y</title>
  <style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body {
      font-family:'Segoe UI', Tahoma, Arial, sans-serif;
      background:#f3f4f6; color:#333;
    }
    a { text-decoration:none; color:#007bff; }
    a:hover { color:#0056b3; }

    /* Navigation */
    .nav {
      background:#fff; padding:15px; margin-bottom:20px;
      display:flex; align-items:center; justify-content:space-between;
      box-shadow:0 2px 10px rgba(0,0,0,0.05);
    }
    .nav-left, .nav-right {
      display:flex; gap:15px; align-items:center;
    }
    .admin-label { color:crimson; margin-left:5px; }

    .container {
      max-width:1100px; margin:0 auto; padding:20px;
    }

    /* Buttons / Inputs */
    button, .btn {
      background:#28a745; color:#fff; border:none;
      padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:bold;
      display:inline-block; text-align:center;
    }
    button:hover, .btn:hover {
      background:#218838;
    }
    .delete {
      background:#dc3545; color:#fff;
    }
    .delete:hover {
      background:#b02a37;
    }
    .close-btn {
      background:#6c757d;
    }
    .close-btn:hover {
      background:#5a6268;
    }

    /* Listenansicht */
    .filterbar {
      display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:25px;
    }
    .filterbar input[type="text"], .filterbar select {
      padding:8px 12px; border:1px solid #ccc; border-radius:6px; flex:1;
    }

    .movie-grid {
      display:grid; gap:20px; grid-template-columns:repeat(auto-fill, minmax(250px,1fr));
    }
    .movie-card {
      background:#fff; border-radius:8px; padding:15px; box-shadow:0 2px 5px rgba(0,0,0,0.05);
      display:flex; flex-direction:column;
      transition:transform 0.1s;
    }
    .movie-card:hover {
      transform:scale(1.01);
    }
    .movie-card h3 {
      margin-bottom:8px; font-size:1.2em; color:#111;
    }
    .meta { font-size:0.9em; color:#666; margin-bottom:10px; }
    .actions {
      margin-top:auto; display:flex; flex-wrap:wrap; gap:8px;
    }

    /* Detailansicht */
    .film-detail {
      background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 5px rgba(0,0,0,0.05);
    }
    .film-detail h2 {
      margin-bottom:10px;
    }
    .film-detail .meta {
      font-size:0.95em; color:#666; margin-bottom:15px;
    }
    .review-list { margin-top:15px; }
    .review-item {
      background:#f9f9f9; border:1px solid #eee; border-radius:6px; padding:10px; margin-bottom:8px;
    }
    .review-item .rating { font-weight:bold; }

    /* Pagination mit "←  x von y  →" */
    .pagination {
      display:flex; gap:15px; justify-content:center; margin-top:20px; align-items:center; flex-wrap:wrap;
    }
    .arrow {
      background:#ccc; color:#333; padding:6px 12px; border-radius:4px; font-weight:bold; text-decoration:none;
    }
    .arrow:hover {
      background:#bbb;
    }
    .page-info {
      font-weight:bold; cursor:pointer;
      background:#eee; padding:6px 12px; border-radius:4px; 
    }

    @media (max-width:600px) {
      .filterbar { flex-direction:column; }
      .movie-grid { grid-template-columns:1fr; }
    }
  </style>
  <script>
    // Modal öffnen/schließen
    function openModal(id) {
      document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
    }

    // "Neue Bewertung" Modal öffnen, film_id ins hidden-Feld
    function addReview(filmId) {
      document.getElementById('review_film_id').value = filmId;
      openModal('review-modal-overlay');
    }

    // Seitenzahl per Prompt ändern
    function changePage(current, total) {
      const value = prompt(`Aktuelle Seite: ${current}\nGesamtseiten: ${total}\nNeue Seitenzahl eingeben:`, current);
      if (value !== null) {
        const newPage = parseInt(value, 10);
        if (!isNaN(newPage) && newPage >= 1 && newPage <= total) {
          // URL-Parameter anpassen
          const params = new URLSearchParams(window.location.search);
          params.set('page', newPage);
          window.location.search = params.toString();
        } else {
          alert("Ungültige Seite!");
        }
      }
    }
  </script>
</head>
<body>

<!-- Navigation -->
<div class="nav">
  <div class="nav-left">
    <a href="index.php">Home</a>
  </div>
  <div class="nav-right">
    <?php if (isLoggedIn()): ?>
      <span><?= htmlspecialchars($_SESSION['username']) ?></span>
      <?php if ($isAdmin): ?>
        <span class="admin-label">(Admin)</span>
      <?php endif; ?>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register_form.php">Registrieren</a>
    <?php endif; ?>
  </div>
</div>

<div class="container">
<?php
// Detailansicht
if ($filmId):
    $film = getFilmById($filmId);
    if (!$film) {
        echo "<p>Film nicht gefunden.</p>";
    } else {
        $reviews = getReviewsByFilm($filmId);
        ?>
        <div class="film-detail">
          <h2><?= htmlspecialchars($film->titel) ?></h2>
          <div class="meta">
            <strong>Bewertung:</strong> <?= number_format($film->bewertung,1) ?><br>
            <strong>Genre:</strong> <?= htmlspecialchars($film->genre) ?><br>
            <strong>Regisseur:</strong> <?= htmlspecialchars($film->regisseur) ?>
          </div>

          <?php if ($isAdmin): ?>
            <div style="margin-bottom:15px;">
              <a class="btn" href="edit_film.php?film=<?= urlencode($filmId) ?>">Bearbeiten</a>
              <a class="delete" href="?delete_movie=<?= urlencode($filmId) ?>">Film löschen</a>
            </div>
          <?php endif; ?>

          <h3>Bewertungen:</h3>
          <div class="review-list">
            <?php if (count($reviews) === 0): ?>
              <p style="color:#666;">(Keine Bewertungen vorhanden)</p>
            <?php else: ?>
              <?php foreach ($reviews as $rev): ?>
                <div class="review-item">
                  <span class="rating">★ <?= $rev->rating ?>/5</span>
                  <p><?= nl2br(htmlspecialchars($rev->text)) ?></p>
                  <?php if ($isAdmin): ?>
                    <a href="?delete_review=<?= (string)$rev->_id ?>&film_id=<?= urlencode($filmId) ?>" style="color:red;">[Löschen]</a>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <?php if (isLoggedIn()): ?>
            <p><button onclick="addReview('<?= $filmId ?>')">+ Bewertung abgeben</button></p>
          <?php endif; ?>

          <p style="margin-top:20px;"><a class="btn" href="index.php">← Zur Übersicht</a></p>
        </div>
        <?php
    }

// Listenansicht
else:
    // Sortierung
    $sortOption = $_GET['sort'] ?? 'default';
    $sort = [];
    switch ($sortOption) {
      case 'rating_desc':
        $sort = ['bewertung' => -1];
        break;
      case 'rating_asc':
        $sort = ['bewertung' => 1];
        break;
      case 'title_asc':
        $sort = ['titel' => 1];
        break;
      case 'title_desc':
        $sort = ['titel' => -1];
        break;
    }

    // Suche
    $search = $_GET['search'] ?? null;

    // Pagination
    $page  = (int)($_GET['page'] ?? 1);
    if ($page < 1) $page = 1;
    $limit = 20;
    $skip  = ($page - 1) * $limit;

    // Filter
    $filter = [];
    if ($search) {
        $filter = ['titel' => new MongoDB\BSON\Regex($search, 'i')];
    }

    // Gesamtanzahl
    $count = countDocuments('movies', $filter);
    $totalPages = max(1, ceil($count / $limit));

    // Filme laden
    $cursor = fetchCollection('movies', $filter, $sort, $skip, $limit);
    $movies = $cursor->toArray();
    ?>
    <div class="filterbar">
      <form method="get" style="flex:1; display:flex; gap:10px;">
        <input type="text" name="search" placeholder="Filmtitel suchen..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="sort">
          <option value="default"     <?= $sortOption==='default'     ? 'selected' : '' ?>>Keine Sort.</option>
          <option value="rating_desc"<?= $sortOption==='rating_desc' ? 'selected' : '' ?>>Beliebteste</option>
          <option value="rating_asc" <?= $sortOption==='rating_asc'  ? 'selected' : '' ?>>Unbeliebteste</option>
          <option value="title_asc"  <?= $sortOption==='title_asc'   ? 'selected' : '' ?>>A–Z</option>
          <option value="title_desc" <?= $sortOption==='title_desc'  ? 'selected' : '' ?>>Z–A</option>
        </select>
        <button type="submit">🔍 Suchen</button>
      </form>
      <?php if ($isAdmin): ?>
        <button style="margin-left:auto;" onclick="openModal('movie-modal-overlay')">+ Film hinzufügen</button>
      <?php endif; ?>
    </div>

    <!-- Film-Liste -->
    <div class="movie-grid">
      <?php foreach ($movies as $m): ?>
        <?php $mid = (string)$m->_id; ?>
        <div class="movie-card">
          <h3>
            <a href="?film=<?= urlencode($mid) ?>">
              <?= htmlspecialchars($m->titel) ?>
            </a>
          </h3>
          <div class="meta">
            Bewertung: <?= number_format($m->bewertung,1) ?><br>
            Genre: <?= htmlspecialchars($m->genre ?? '') ?><br>
            Regisseur: <?= htmlspecialchars($m->regisseur ?? '') ?>
          </div>
          <div class="actions">
            <?php if ($isAdmin): ?>
              <a class="btn" href="edit_film.php?film=<?= urlencode($mid) ?>">Bearbeiten</a>
              <a class="delete" href="?delete_movie=<?= urlencode($mid) ?>">Löschen</a>
            <?php endif; ?>
            <a class="btn" href="?film=<?= urlencode($mid) ?>">Bewertungen</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination: ←  Seite X von Y  → (Seite X klickbar via JS) -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <!-- Pfeil nach links -->
        <?php if ($page > 1): ?>
          <?php
            $prevParams = $_GET;
            $prevParams['page'] = $page - 1;
          ?>
          <a class="arrow" href="?<?= htmlspecialchars(http_build_query($prevParams)) ?>">←</a>
        <?php else: ?>
          <span class="arrow" style="background:#eee; cursor:default;">←</span>
        <?php endif; ?>

        <!-- Aktuelle Seite von totalPages -->
        <span class="page-info" onclick="changePage(<?= $page ?>, <?= $totalPages ?>)">
          Seite <?= $page ?> von <?= $totalPages ?>
        </span>

        <!-- Pfeil nach rechts -->
        <?php if ($page < $totalPages): ?>
          <?php
            $nextParams = $_GET;
            $nextParams['page'] = $page + 1;
          ?>
          <a class="arrow" href="?<?= htmlspecialchars(http_build_query($nextParams)) ?>">→</a>
        <?php else: ?>
          <span class="arrow" style="background:#eee; cursor:default;">→</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

<?php endif; ?>
</div>


</body>
</html>
