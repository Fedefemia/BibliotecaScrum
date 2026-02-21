<?php
require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function highlight_text(?string $text, string $search): string
{
    if ($text === null) return '';
    if ($search === '') return $text;
    return preg_replace('/' . preg_quote($search, '/') . '/iu', '<markGreen>$0</markGreen>', $text);
}

$search_query = trim($_GET['search'] ?? '');

$books = [];
$users = [];
$authors = [];

if (!empty($search_query)) {
    // --- Libri & Autori ---
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
            if (stripos($row['titolo'], $search_query) !== false || stripos($row['isbn'], $search_query) !== false) {
                $books[$row['isbn']] = $row;
            }
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

function getCoverPath(string $isbn): string {
    $localPath = "public/bookCover/$isbn.png";
    if (file_exists($localPath)) {
        return $localPath;
    }
    return "public/assets/book_placeholder.jpg";
}
?>

<?php
$title = "Ricerca - " . $_GET['search'];
$path = "./";
$page_css = "./public/css/style_search.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<div class="page_contents instrument-sans">

    <div class="search_header_container">
        <div class="search_header_top">
            <div class="search_title_block">
                <img src="<?= $path ?>public/assets/icon_search_dark.png" class="search_icon_large" alt="">
                <h2 class="young-serif-regular">Filtri di Ricerca</h2>
            </div>

            <div class="search_tabs">
                <button type="button" id="btn_books" class="general_button_dark search_tab active">Libri</button>
                <button type="button" id="btn_users" class="general_button_dark search_tab">Utenti</button>
            </div>
        </div>

        <div class="search_filters_block">
            <form id="filter_form">
                <div id="filters_books" class="filter_group">
                    <h3>Filtra Libri</h3>
                    <div class="checkbox_wrapper">
                        <label><input type="checkbox" name="filtra_titolo" checked> Titolo</label>
                        <label><input type="checkbox" name="filtra_autore_nome" checked> Nome autore</label>
                        <label><input type="checkbox" name="filtra_autore_cognome" checked> Cognome autore</label>
                    </div>
                </div>
                <div id="filters_users" class="filter_group">
                    <h3>Filtra Utenti</h3>
                    <div class="checkbox_wrapper">
                        <label><input type="checkbox" name="filtra_username" checked> Username</label>
                        <label><input type="checkbox" name="filtra_user_nome" checked> Nome</label>
                        <label><input type="checkbox" name="filtra_user_cognome" checked> Cognome</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <hr class="section_divider">

    <div id="section_books">
        <?php if (empty($books) && empty($authors)): ?>
            <h1 class="young-serif-regular section_title">Nessun risultato</h1>
            <p class="results_meta">Non abbiamo trovato libri o autori per "<strong><?= htmlspecialchars($search_query) ?></strong>" 🤍</p>
        <?php else: ?>

            <?php if (!empty($books)): ?>
                <h1 class="young-serif-regular section_title">Risultati Libri</h1>
                <p class="results_meta">Trovati <strong id="results_count_books"><?= count($books) ?></strong> libri per "<strong><?= htmlspecialchars($search_query) ?></strong>"</p>

                <div id="results_container_books" class="results_grid">
                    <?php foreach ($books as $isbn => $book): ?>
                        <div class="result_card book_card"
                             data-titolo="<?= htmlspecialchars($book['titolo']) ?>"
                             data-autore_nome="<?= htmlspecialchars($book['autore_nome']) ?>"
                             data-autore_cognome="<?= htmlspecialchars($book['autore_cognome']) ?>">

                            <div class="card_image_wrapper">
                                <img src="<?= getCoverPath($book['isbn']) ?>" alt="Cover" class="book_cover">
                            </div>

                            <div class="card_content">
                                <h3 class="card_title"><?= highlight_text($book['titolo'], $search_query) ?></h3>
                                <p class="card_info">
                                    <span>Autore:</span>
                                    <?= highlight_text($book['autore_nome'] . ' ' . $book['autore_cognome'], $search_query) ?>
                                </p>
                                <a href="./libro?isbn=<?= urlencode($isbn) ?>" class="card_link">Dettagli &rarr;</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($books) && !empty($authors)): ?>
                <div class="spacer_large"></div>
            <?php endif; ?>

            <?php if (!empty($authors)): ?>
                <h2 class="young-serif-regular section_title">Risultati Autori</h2>
                <p class="results_meta">Trovati <strong id="results_count_authors"><?= count($authors) ?></strong> autori</p>

                <div id="results_container_authors" class="results_grid">
                    <?php foreach ($authors as $isbn => $author_book): ?>
                        <div class="result_card author_card"
                             data-autore_nome="<?= htmlspecialchars($author_book['autore_nome']) ?>"
                             data-autore_cognome="<?= htmlspecialchars($author_book['autore_cognome']) ?>">

                            <div class="card_image_wrapper">
                                <img src="<?= getCoverPath($author_book['isbn']) ?>" alt="Cover" class="book_cover">
                            </div>

                            <div class="card_content">
                                <h3 class="card_title">
                                    <?= highlight_text($author_book['autore_nome'] . ' ' . $author_book['autore_cognome'], $search_query) ?>
                                </h3>
                                <p class="card_info">Trovato nel libro: <em><?= htmlspecialchars($author_book['titolo']) ?></em></p>
                                <a href="./libro?isbn=<?= urlencode($author_book['isbn']) ?>" class="card_link">Vedi opera &rarr;</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    </div>

    <div id="section_users" style="display:none;">
        <h1 class="young-serif-regular section_title">Risultati Utenti</h1>
        <p class="results_meta">Trovati <strong id="results_count_users"><?= count($users) ?></strong> utenti</p>

        <div id="results_container_users" class="results_grid">
            <?php foreach ($users as $user): ?>
                <div class="result_card user_card"
                     style="cursor: pointer;"
                     onclick="location.href='./pubblico?username=<?= urlencode($user['username']) ?>'"
                     data-username="<?= $user['username'] ?>"
                     data-user_nome="<?= $user['nome'] ?>"
                     data-user_cognome="<?= $user['cognome'] ?>">

                    <div class="card_content full_width">
                        <h3 class="card_title">@<?= highlight_text($user['username'], $search_query) ?></h3>
                        <p class="card_info">
                            <?= highlight_text($user['nome'], $search_query) ?>
                            <?= highlight_text($user['cognome'], $search_query) ?>
                        </p>
                        <a href="./pubblico?username=<?= urlencode($user['username']) ?>" class="card_link_subtle">Vedi Profilo</a>
                    </div>
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

    const rawSearchQuery = "<?= addslashes($search_query) ?>";
    const searchQuery = rawSearchQuery.toLowerCase();

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

    if (savedSection === 'users') showUsers(); else showBooks();

    btnBooks.addEventListener('click', showBooks);
    btnUsers.addEventListener('click', showUsers);

    function filterResults() {
        const activeFiltersBooks = Array.from(document.querySelectorAll('#filters_books input:checked')).map(cb => cb.name.replace('filtra_', ''));
        const activeFiltersUsers = Array.from(document.querySelectorAll('#filters_users input:checked')).map(cb => cb.name.replace('filtra_', ''));

        let visibleBooks = 0;
        document.querySelectorAll('.book_card').forEach(card => {
            const show = activeFiltersBooks.some(field =>
                (card.dataset[field] || '').toLowerCase().includes(searchQuery)
            );
            card.style.display = show ? 'flex' : 'none';
            if (show) visibleBooks++;
        });
        document.getElementById('results_count_books').textContent = visibleBooks;

        let visibleAuthors = 0;
        document.querySelectorAll('.author_card').forEach(card => {
            const show = activeFiltersBooks.some(field =>
                field.includes('autore') && (card.dataset[field] || '').toLowerCase().includes(searchQuery)
            );
            card.style.display = show ? 'flex' : 'none';
            if (show) visibleAuthors++;
        });
        document.getElementById('results_count_authors').textContent = visibleAuthors;

        let visibleUsers = 0;
        document.querySelectorAll('.user_card').forEach(card => {
            const show = activeFiltersUsers.some(field =>
                (card.dataset[field] || '').toLowerCase().includes(searchQuery)
            );
            card.style.display = show ? 'flex' : 'none';
            if (show) visibleUsers++;
        });
        document.getElementById('results_count_users').textContent = visibleUsers;
    }

    checkboxes.forEach(cb => cb.addEventListener('change', filterResults));
    filterResults();
</script>

<?php require './src/includes/footer.php'; ?>