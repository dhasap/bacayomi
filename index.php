<?php
require_once 'includes/functions.php';

$available_sources = array_keys($SCRAPER_CONFIG);
$current_source_key = $available_sources[0];
if (isset($_GET['source']) && in_array($_GET['source'], $available_sources)) {
    $current_source_key = $_GET['source'];
}

$comic_list = [];
if (isset($_GET['source'])) {
    $comic_list = get_latest_comics_from_source($current_source_key);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bacayomi - Baca Komik Online</title>
    
    <!-- Optimasi Font: Preconnect dan load dengan display=swap -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Link ke file CSS yang sudah di-minify -->
    <link rel="stylesheet" href="assets/style.min.css">
</head>
<body>

    <header>
        <h1>Bacayomi</h1>
        <p>Pilih sumber dan mulai membaca komik favoritmu.</p>
    </header>

    <div class="container">
        <main>
            <div class="source-selector">
                <form action="index.php" method="GET" id="sourceForm">
                    <label for="source">Sumber:</label>
                    <select name="source" id="source">
                        <?php foreach ($available_sources as $source_key): ?>
                            <option value="<?php echo $source_key; ?>" <?php if ($source_key == $current_source_key) echo 'selected'; ?>>
                                <?php echo ucfirst($source_key); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="display: none;">Tampilkan</button>
                </form>
            </div>
            
            <div id="loading-indicator">
                <div class="loader"></div>
                <p>Memuat data dari <?php echo ucfirst($current_source_key); ?>...</p>
            </div>

            <div class="comic-grid">
                <?php if (!empty($comic_list)): ?>
                    <?php foreach ($comic_list as $comic): ?>
                        <?php
                        $thumbnail = $comic['image'] ?? $comic['thumbnail'];
                        $read_url = 'reader.php?source=' . urlencode($current_source_key) . '&url=' . urlencode($comic['url']);
                        ?>
                        <div class="comic-card">
                            <a href="<?php echo htmlspecialchars($read_url); ?>" class="cover-link">
                                <img src="<?php echo htmlspecialchars($thumbnail); ?>" alt="<?php echo htmlspecialchars($comic['title']); ?>" loading="lazy">
                            </a>
                            <div class="title" title="<?php echo htmlspecialchars($comic['title']); ?>">
                                <?php echo htmlspecialchars($comic['title']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (isset($_GET['source'])): ?>
                    <div class="no-data">
                        <p>Gagal memuat data dari <strong><?php echo ucfirst($current_source_key); ?></strong> atau tidak ada komik terbaru.</p>
                    </div>
                <?php else: ?>
                     <div class="no-data">
                        <p>Silakan pilih sumber komik untuk memulai.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Bacayomi. Dibuat untuk tujuan edukasi.</p>
    </footer>

    <button id="scrollToTopBtn" title="Kembali ke atas">&uarr;</button>

    <script src="assets/script.min.js"></script>

</body>
</html>

