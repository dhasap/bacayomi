<?php
/**
 * File: komikcast.php
 * Deskripsi: Modul scraper untuk situs Komikcast (komikcast.lol).
 * Project: Bacayomi
 *
 * Dibuat tanpa library eksternal, hanya menggunakan cURL dan DOMDocument.
 * Fungsi utama:
 * 1. getKomikList(): Mengambil daftar komik terbaru dari halaman utama.
 * 2. getChapter(string $komikId): Mengambil daftar chapter dari sebuah komik.
 * 3. getChapterImages(string $chapterUrl): Mengambil gambar dari sebuah chapter.
 */

// URL dasar dari situs yang akan di-scrape, sesuai info ekstensi.
define('KOMIKCAST_BASE_URL', 'https://komikcast.lol');

/**
 * Fungsi helper untuk mengambil konten HTML dari sebuah URL menggunakan cURL.
 * Fungsi ini sudah dilengkapi User-Agent untuk bypass proteksi Cloudflare sederhana.
 *
 * @param string $url URL halaman yang akan diambil.
 * @return string|false Konten HTML sebagai string, atau false jika gagal.
 */
function fetchHtml(string $url): string|false
{
    // Inisialisasi cURL
    $ch = curl_init();

    // Setel opsi cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Mengembalikan hasil sebagai string
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Mengikuti redirect (penting untuk Cloudflare)
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Timeout dalam detik

    // Setel User-Agent yang umum untuk menghindari blokir sederhana.
    // Ini penting karena situs target menggunakan Cloudflare (hasCloudflare: 1).
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

    // Nonaktifkan verifikasi SSL (berguna untuk lingkungan development/shared hosting)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    // Eksekusi cURL
    $html = curl_exec($ch);

    // Cek jika ada error
    if (curl_errno($ch)) {
        // Sebaiknya di-log, bukan di-echo langsung di production
        // error_log('cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    // Tutup cURL
    curl_close($ch);

    return $html;
}

/**
 * Mengambil daftar komik terbaru dari halaman utama Komikcast.
 *
 * @return array Daftar komik dengan format [['title', 'image', 'id'], ...].
 */
function getKomikList(): array
{
    $html = fetchHtml(KOMIKCAST_BASE_URL);
    if (!$html) {
        return [];
    }

    $komikList = [];
    $dom = new DOMDocument();
    // Menggunakan @ untuk menekan warning dari HTML yang mungkin tidak valid
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Query XPath untuk menemukan setiap item komik di daftar update terbaru
    // Targetnya adalah div dengan class 'list-update_item'
    $nodes = $xpath->query("//div[contains(@class, 'list-update_item')]");

    foreach ($nodes as $node) {
        // Mengambil judul dari tag h3 di dalam item
        $titleNode = $xpath->query(".//h3[contains(@class, 'list-update_item-title')]", $node)->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : 'Tidak ada judul';

        // Mengambil URL gambar dari atribut 'src' pada tag img
        $imageNode = $xpath->query(".//img[contains(@class, 'list-update_item-img-in')]/@src", $node)->item(0);
        $image = $imageNode ? trim($imageNode->nodeValue) : '';

        // Mengambil URL komik untuk mendapatkan ID (slug)
        $linkNode = $xpath->query(".//a/@href", $node)->item(0);
        $link = $linkNode ? trim($linkNode->nodeValue) : '';
        
        // Ekstrak ID (slug) dari URL. Contoh: https://komikcast.lol/komik/one-piece/ -> one-piece
        $id = '';
        if ($link) {
            $path = parse_url($link, PHP_URL_PATH);
            // Membersihkan slug dari path '/komik/' dan slash di akhir
            $id = str_replace('/komik/', '', rtrim($path, '/'));
        }

        // Hanya tambahkan ke list jika ID berhasil didapatkan
        if ($id) {
            $komikList[] = [
                'title' => $title,
                'image' => $image,
                'id' => $id,
            ];
        }
    }

    return $komikList;
}

/**
 * Mengambil daftar chapter dari sebuah komik berdasarkan ID (slug) komiknya.
 *
 * @param string $komikId ID atau slug dari komik (contoh: 'one-piece').
 * @return array Daftar chapter.
 */
function getChapter(string $komikId): array
{
    // Membentuk URL halaman detail komik
    $url = KOMIKCAST_BASE_URL . '/komik/' . $komikId . '/';
    $html = fetchHtml($url);
    if (!$html) {
        return [];
    }

    $chapterList = [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Query XPath untuk menemukan setiap list item chapter
    // Targetnya adalah <li> di dalam div dengan class 'clstyle'
    $nodes = $xpath->query("//div[contains(@class, 'clstyle')]//li");

    foreach ($nodes as $node) {
        // Mengambil judul chapter
        $titleNode = $xpath->query(".//span[contains(@class, 'chapternum')]", $node)->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : 'N/A';

        // Mengambil URL chapter
        $urlNode = $xpath->query(".//a/@href", $node)->item(0);
        $chapterUrl = $urlNode ? trim($urlNode->nodeValue) : '';
        
        // Mengambil tanggal rilis
        $dateNode = $xpath->query(".//span[contains(@class, 'chapterdate')]", $node)->item(0);
        $releaseDate = $dateNode ? trim($dateNode->textContent) : 'N/A';
        
        // Mengambil ID chapter dari URL
        $chapterId = '';
        if ($chapterUrl) {
            $path = parse_url($chapterUrl, PHP_URL_PATH);
            $chapterId = basename(rtrim($path, '/'));
        }

        if ($chapterUrl && $chapterId) {
            $chapterList[] = [
                'title' => $title,
                'id' => $chapterId, // contoh: 'chapter-1120'
                'url' => $chapterUrl,
                'date' => $releaseDate,
            ];
        }
    }

    return $chapterList;
}

/**
 * Mengambil semua URL gambar dari halaman chapter.
 *
 * @param string $chapterId ID atau slug dari chapter (contoh: 'chapter-1120').
 * @return array Daftar URL gambar.
 */
function getChapterImages(string $chapterId): array
{
    $url = KOMIKCAST_BASE_URL . '/' . $chapterId . '/';
    $html = fetchHtml($url);
    if (!$html) {
        return [];
    }

    $images = [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Query XPath untuk menemukan semua gambar di dalam area baca
    // Targetnya adalah tag <img> di dalam div dengan id 'readerarea'
    $nodes = $xpath->query("//div[@id='readerarea']//img/@src");

    foreach ($nodes as $node) {
        $imageUrl = trim($node->nodeValue);
        // Memastikan URL tidak kosong dan valid
        if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
             $images[] = $imageUrl;
        }
    }

    return $images;
}


/*
// --- CONTOH PENGGUNAAN ---
// Hapus atau beri komentar bagian ini saat file di-include ke project utama.

// 1. Mengambil daftar komik terbaru
echo "<h1>Daftar Komik Terbaru</h1>";
$list = getKomikList();
if (!empty($list)) {
    echo "<ul>";
    foreach (array_slice($list, 0, 5) as $komik) { // Tampilkan 5 pertama
        echo "<li>";
        echo "<img src='{$komik['image']}' width='50' alt='cover' style='vertical-align: middle; margin-right: 10px;' /> ";
        echo "<strong>{$komik['title']}</strong> (ID: {$komik['id']})";
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Gagal mengambil daftar komik.</p>";
}

echo "<hr>";

// 2. Mengambil daftar chapter untuk komik 'solo-leveling'
$testKomikId = 'solo-leveling';
echo "<h1>Daftar Chapter untuk '{$testKomikId}'</h1>";
$chapters = getChapter($testKomikId);
if (!empty($chapters)) {
    echo "<ul>";
    foreach (array_slice($chapters, 0, 5) as $chapter) { // Tampilkan 5 chapter pertama
        echo "<li><a href='{$chapter['url']}' target='_blank'>{$chapter['title']}</a> (ID: {$chapter['id']}) - <em>Rilis: {$chapter['date']}</em></li>";
    }
    echo "</ul>";

    echo "<hr>";
    
    // 3. Mengambil gambar dari chapter pertama yang ditemukan
    $firstChapterId = $chapters[0]['id'];
    echo "<h1>Gambar untuk Chapter '{$firstChapterId}'</h1>";
    $images = getChapterImages($firstChapterId);
    if(!empty($images)) {
        echo "<div style='display:flex; flex-direction:column; align-items:center;'>";
        foreach(array_slice($images, 0, 3) as $img) { // Tampilkan 3 gambar pertama
            echo "<img src='{$img}' alt='Gambar Chapter' style='max-width: 100%; margin-bottom: 5px; border: 1px solid #ccc;' />";
        }
        echo "</div>";
    } else {
        echo "<p>Gagal mengambil gambar chapter.</p>";
    }

} else {
    echo "<p>Gagal mengambil daftar chapter atau komik tidak ditemukan.</p>";
}
*/
?>

