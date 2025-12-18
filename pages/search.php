<?php
require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function highlight_text(?string $text, string $search): string {
    if ($text === null) return '';
    if ($search === '') return $text;
    return preg_replace('/' . preg_quote($search, '/') . '/iu', '<mark>$0</mark>', $text);
}

$search_query = trim($_GET['search'] ?? '');

$books = [];
$users = [];
$authors = [];

if (!empty($search_query)) {
    // --- Libri ---
    $sql_books = "
        SELECT l.isbn, l.titolo, 
               GROUP_CONCAT(DISTINCT a.nome SEPARATOR ', ') AS autore_nome,
               GROUP_CONCAT(DISTINCT a.cognome SEPARATOR ', ') AS autore_cognome
        FROM libri l
        LEFT JOIN autore_libro al ON al.isbn = l.isbn
        LEFT JOIN autori a ON a.id_autore = al.id_autore
        GROUP BY l.isbn
        ORDER BY l.titolo ASC
    ";
    try {
        $stmt = $pdo->query($sql_books);
        $all_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($all_books as $row) {
            // Libri solo se il titolo contiene il termine di ricerca
            if (stripos($row['titolo'], $search_query) !== false) {
                $books[$row['isbn']] = $row;
            }
            // Autori solo se nome o cognome contengono il termine
            if (!empty($row['autore_nome']) && !empty($row['autore_cognome']) &&
                    (stripos($row['autore_nome'], $search_query) !== false || stripos($row['autore_cognome'], $search_query) !== false)) {
                $authors[$row['isbn']] = $row;
            }
        }
    } catch (PDOException $e) {
        $books = [];
        $authors = [];
    }

    // --- Utenti ---
    $sql_users = "SELECT username, nome, cognome, email FROM utenti ORDER BY username ASC";
    try {
        $stmt = $pdo->query($sql_users);
        $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($all_users as $user) {
            if (stripos($user['username'], $search_query) !== false ||
                    stripos($user['nome'], $search_query) !== false ||
                    stripos($user['cognome'], $search_query) !== false) {
                $users[] = $user;
            }
        }
    } catch (PDOException $e) {
        $users = [];
    }
}

require './src/includes/header.php';
require './src/includes/navbar.php';
?>

<div class="page_contents">

    <h2>🔎 Filtri di Ricerca</h2>

    <!-- Pulsanti per selezionare la sezione da visualizzare -->
    <div style="margin-bottom:15px;">
        <button type="button" id="btn_books" class="section_btn active">Libri</button>
        <button type="button" id="btn_users" class="section_btn">Utenti</button>
    </div>

    <!-- Form filtri -->
    <form id="filter_form">
        <div id="filters_books">
            <h3>Libri</h3>
            <label><input type="checkbox" name="filtra_titolo" checked> Titolo libro</label><br>
            <label><input type="checkbox" name="filtra_autore_nome" checked> Nome autore</label><br>
            <label><input type="checkbox" name="filtra_autore_cognome" checked> Cognome autore</label><br>
        </div>
        <div id="filters_users" style="display:none;">
            <h3>Utenti</h3>
            <label><input type="checkbox" name="filtra_username" checked> Username utente</label><br>
            <label><input type="checkbox" name="filtra_user_nome" checked> Nome utente</label><br>
            <label><input type="checkbox" name="filtra_user_cognome" checked> Cognome utente</label>
        </div>
    </form>

    <hr>

    <!-- SEZIONE LIBRI -->
    <div id="section_books">
        <h1>Risultati Libri</h1>
        <p>Trovati <strong id="results_count_books"><?= count($books) ?></strong> libri per <strong><?= $search_query ?></strong></p>
        <div id="results_container_books">
            <?php foreach ($books as $isbn => $book): ?>
                <div class="book_card"
                     data-titolo="<?= $book['titolo'] ?>"
                     data-autore_nome="<?= $book['autore_nome'] ?>"
                     data-autore_cognome="<?= $book['autore_cognome'] ?>"
                     style="margin-bottom:10px; display:flex; align-items:center;">
                    <img src="public/bookCover<?= $book['isbn'] ?? 'src/assets/placeholder' ?>.jpg" alt="Copertina" style="width:50px;height:70px;margin-right:10px;">
                    <div>
                        <h3 class="book_titolo"><?= highlight_text($book['titolo'], $search_query) ?></h3>
                        <p class="book_autore_nome"><strong>Nome autore:</strong> <?= highlight_text($book['autore_nome'], $search_query) ?></p>
                        <p class="book_autore_cognome"><strong>Cognome autore:</strong> <?= highlight_text($book['autore_cognome'], $search_query) ?></p>
                        <a href="./libro?isbn=<?= urlencode($isbn) ?>">Dettagli</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr>

        <h2>Risultati Autori</h2>
        <p>Trovati <strong id="results_count_authors"><?= count($authors) ?></strong> autori per <strong><?= $search_query ?></strong></p>
        <div id="results_container_authors">
            <?php foreach ($authors as $isbn => $book): ?>
                <div class="author_card"
                     data-autore_nome="<?= $book['autore_nome'] ?>"
                     data-autore_cognome="<?= $book['autore_cognome'] ?>"
                     style="margin-bottom:10px; display:flex; align-items:center;">
                    <img src="public/bookCover<?= $book['isbn'] ?? 'src/assets/placeholder' ?>.jpg" alt="Copertina" style="width:50px;height:70px;margin-right:10px;">
                    <div>
                        <p class="author_nome"><strong>Nome autore:</strong> <?= highlight_text($book['autore_nome'], $search_query) ?></p>
                        <p class="author_cognome"><strong>Cognome autore:</strong> <?= highlight_text($book['autore_cognome'], $search_query) ?></p>
                        <p><strong>Libro:</strong> <?= $book['titolo'] ?></p>
                        <a href="./libro?isbn=<?= urlencode($isbn) ?>">Dettagli</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SEZIONE UTENTI -->
    <div id="section_users" style="display:none;">
        <h1>Risultati Utenti</h1>
        <p>Trovati <strong id="results_count_users"><?= count($users) ?></strong> utenti per <strong><?= $search_query ?></strong></p>
        <div id="results_container_users">
            <?php foreach ($users as $user): ?>
                <div class="user_card"
                     data-username="<?= $user['username'] ?>"
                     data-user_nome="<?= $user['nome'] ?>"
                     data-user_cognome="<?= $user['cognome'] ?>"
                     style="margin-bottom:10px;">
                    <p class="user_username"><strong>Username:</strong> <?= highlight_text($user['username'], $search_query) ?></p>
                    <p class="user_user_nome"><strong>Nome:</strong> <?= highlight_text($user['nome'], $search_query) ?></p>
                    <p class="user_user_cognome"><strong>Cognome:</strong> <?= highlight_text($user['cognome'], $search_query) ?></p>
                    <a href="/profilo?username=<?= urlencode($user['username']) ?>">Visualizza profilo</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
    const btnBooks = document.getElementById('btn_books');
    const btnUsers = document.getElementById('btn_users');
    const sectionBooks = document.getElementById('section_books');
    const sectionUsers = document.getElementById('section_users');
    const filtersBooks = document.getElementById('filters_books');
    const filtersUsers = document.getElementById('filters_users');
    const checkboxes = document.querySelectorAll('#filter_form input[type=checkbox]');
    const searchQuery = '<?= addslashes($search_query) ?>'.toLowerCase();

    /* ===============================
       🧠 MEMORIA SEZIONE ATTIVA
    =============================== */

    // Se arrivo da un'altra pagina → default Libri
    if (document.referrer && !document.referrer.includes('search')) {
        sessionStorage.setItem('activeSearchSection', 'books');
    }

    const savedSection = sessionStorage.getItem('activeSearchSection') || 'books';

    function showBooks() {
        sectionBooks.style.display = 'block';
        sectionUsers.style.display = 'none';
        filtersBooks.style.display = 'block';
        filtersUsers.style.display = 'none';
        btnBooks.classList.add('active');
        btnUsers.classList.remove('active');
        sessionStorage.setItem('activeSearchSection', 'books');
    }

    function showUsers() {
        sectionBooks.style.display = 'none';
        sectionUsers.style.display = 'block';
        filtersBooks.style.display = 'none';
        filtersUsers.style.display = 'block';
        btnBooks.classList.remove('active');
        btnUsers.classList.add('active');
        sessionStorage.setItem('activeSearchSection', 'users');
    }

    // Applica la sezione iniziale
    if (savedSection === 'users') {
        showUsers();
    } else {
        showBooks();
    }

    // Eventi pulsanti
    btnBooks.addEventListener('click', showBooks);
    btnUsers.addEventListener('click', showUsers);

    /* ===============================
       🔎 HIGHLIGHT TESTO
    =============================== */

    function highlightText(text, search) {
        if (!search) return text;
        const regex = new RegExp(`(${search})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    /* ===============================
       🎛️ FILTRI ON-CHANGE
    =============================== */

    function filterResults() {
        // Filtri libri attivi
        const activeFiltersBooks = Array.from(checkboxes)
            .filter(cb => cb.checked && cb.closest('#filters_books'))
            .map(cb => cb.name.replace('filtra_', ''));

        // Filtri utenti attivi
        const activeFiltersUsers = Array.from(checkboxes)
            .filter(cb => cb.checked && cb.closest('#filters_users'))
            .map(cb => cb.name.replace('filtra_', ''));

        /* ---------- LIBRI ---------- */
        let visibleBooks = 0;
        document.querySelectorAll('.book_card').forEach(card => {
            const show = activeFiltersBooks.some(field =>
                (card.dataset[field] || '').toLowerCase().includes(searchQuery)
            );
            card.style.display = show ? 'flex' : 'none';
            if (show) {
                visibleBooks++;
                card.querySelectorAll('h3,p').forEach(el => {
                    const field = el.className.replace('book_', '');
                    if (activeFiltersBooks.includes(field)) {
                        el.innerHTML = highlightText(card.dataset[field] || '', searchQuery);
                    }
                });
            }
        });
        document.getElementById('results_count_books').textContent = visibleBooks;

        /* ---------- AUTORI ---------- */
        let visibleAuthors = 0;
        document.querySelectorAll('.author_card').forEach(card => {
            const show = activeFiltersBooks.some(field =>
                (card.dataset[field] || '').toLowerCase().includes(searchQuery)
            );
            card.style.display = show ? 'flex' : 'none';
            if (show) {
                visibleAuthors++;
                card.querySelectorAll('p').forEach(el => {
                    const field = el.className.replace('author_', '');
                    if (activeFiltersBooks.includes(field)) {
                        el.innerHTML = highlightText(card.dataset[field] || '', searchQuery);
                    }
                });
            }
        });
        document.getElementById('results_count_authors').textContent = visibleAuthors;

        /* ---------- UTENTI ---------- */
        let visibleUsers = 0;
        document.querySelectorAll('.user_card').forEach(card => {
            const show = activeFiltersUsers.some(field =>
                (card.dataset[field] || '').toLowerCase().includes(searchQuery)
            );
            card.style.display = show ? 'block' : 'none';
            if (show) {
                visibleUsers++;
                card.querySelectorAll('p').forEach(el => {
                    const field = el.className.replace('user_', '');
                    if (activeFiltersUsers.includes(field)) {
                        el.innerHTML = highlightText(card.dataset[field] || '', searchQuery);
                    }
                });
            }
        });
        document.getElementById('results_count_users').textContent = visibleUsers;
    }

    // Eventi checkbox
    checkboxes.forEach(cb => cb.addEventListener('change', filterResults));

    // Filtro iniziale
    filterResults();
</script>


<?php require './src/includes/footer.php'; ?>
