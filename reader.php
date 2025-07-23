<?php
require_once 'includes/functions.php';

$source_name = $_GET['source'] ?? null;
$comic_url = $_GET['url'] ?? null;
$chapter_url = $_GET['chapter_url'] ?? null;

if (!$source_name || !$comic_url) {
    die("Error: Informasi sumber atau URL komik tidak lengkap.");
}

if ($chapter_url) {
    $image_list = get_chapter_images_from_source($source_name, $chapter_url);
    $page_title = $_GET['chapter_title'] ?? 'Baca Komik';
} else {
    $comic_detail = get_comic_detail_from_source($source_name, $comic_url);
    $page_title = $comic_detail['title'] ?? 'Detail Komik';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Bacayomi</title>

    <!-- Optimasi Font: Preconnect dan load dengan display=swap -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/style.min.css">
</head>
<body class="reader-mode">

    <header>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="index.php" style="color:white; text-decoration: underline;">&larr; Halaman Utama</a>
    </header>

    <div class="container">
        <main>
            <?php if ($chapter_url): ?>
                <div class="reader-images">
                    <?php if (!empty($image_list)): ?>
                        <?php foreach ($image_list as $image_url): ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Halaman Komik" loading="lazy">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Gagal memuat gambar chapter.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if (!empty($comic_detail)): ?>
                    <div class="comic-detail-container">
                        <div class="comic-cover">
                            <img src="<?php echo htmlspecialchars($comic_detail['image']); ?>" alt="Cover <?php echo htmlspecialchars($comic_detail['title']); ?>">
                        </div>
                        <div class="comic-info">
                            <h2><?php echo htmlspecialchars($comic_detail['title']); ?></h2>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars($comic_detail['status']); ?></p>
                            <p><strong>Author:</strong> <?php echo htmlspecialchars($comic_detail['author']); ?></p>
                            <div class="genres">
                                <?php foreach($comic_detail['genres'] as $genre): ?>
                                    <a href="#"><?php echo htmlspecialchars($genre); ?></a>
                                <?php endforeach; ?>
                            </div>
                            <h3>Sinopsis</h3>
                            <p><?php echo nl2br(htmlspecialchars($comic_detail['synopsis'])); ?></p>
                        </div>
                    </div>
                    <hr style="margin: 30px 0; border-color: #333;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <h3>Daftar Chapter</h3>
                        <button id="chapterToggleBtn">Ciutkan Daftar</button>
                    </div>
                    <br>
                    <ul class="chapter-list">
                        <?php if(!empty($comic_detail['chapters'])): ?>
                            <?php foreach($comic_detail['chapters'] as $chapter): ?>
                                <?php
                                $link_to_chapter = 'reader.php?source=' . urlencode($source_name) . '&url=' . urlencode($comic_url) . '&chapter_url=' . urlencode($chapter['url']) . '&chapter_title=' . urlencode($chapter['title']);
                                ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($link_to_chapter); ?>">
                                        <?php echo htmlspecialchars($chapter['title']); ?>
                                        <small style="display: block; color: #888;"><?php echo htmlspecialchars($chapter['date']); ?></small>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>Tidak ada chapter ditemukan.</li>
                        <?php endif; ?>
                    </ul>
                <?php else: ?>
                    <p>Gagal memuat detail komik.</p>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Bacayomi. Selamat Membaca!</p>
    </footer>

    <button id="scrollToTopBtn" title="Kembali ke atas">&uarr;</button>

    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script src="assets/script.min.js"></script>
</body>
</html>

