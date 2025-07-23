<?php
/**
 * File: shinigami.php
 * Deskripsi: Modul scraper untuk situs Shinigami (shinigamitoon.com).
 * Project: Bacayomi (bacayomi.rf.gd)
 *
 * Dibuat tanpa library eksternal, hanya menggunakan cURL dan DOMDocument.
 * Fungsi utama:
 * 1. get_latest_comics(): Mengambil daftar komik terbaru dari halaman utama.
 * 2. get_comic_detail(string $url): Mengambil detail lengkap dari sebuah komik.
 * 3. get_chapter_images(string $url): Mengambil semua gambar dari sebuah chapter.
 */

// URL dasar dari situs yang akan di-scrape, sesuai info ekstensi Tachiyomi.
define('SHINIGAMI_BASE_URL', 'https://shinigamitoon.com');

/**
 * Fungsi helper untuk mengambil konten HTML dari sebuah URL menggunakan cURL.
 * Dilengkapi User-Agent untuk bypass proteksi Cloudflare sederhana.
 *
 * @param string $url URL halaman yang akan diambil.
 * @return string|false Konten HTML sebagai string, atau false jika gagal.
 */
function fetch_page_content(string $url): string|false
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Penting untuk redirect Cloudflare
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // User-Agent ini penting karena situs target menggunakan Cloudflare (hasCloudflare: 1)
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36');
    // Nonaktifkan verifikasi SSL untuk kompatibilitas hosting gratis
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $html = curl_exec($ch);
    if (curl_errno($ch)) {
        // Di lingkungan produksi, sebaiknya error ini dicatat ke file log.
        // error_log('cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    return $html;
}

/**
 * Mengambil daftar komik terbaru dari halaman utama Shinigami.
 *
 * @return array Daftar komik dengan format [['title', 'url', 'thumbnail'], ...].
 */
function get_latest_comics(): array
{
    $html = fetch_page_content(SHINIGAMI_BASE_URL);
    if (!$html) {
        return [];
    }

    $comics = [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html); // @ menekan warning dari HTML yang tidak sempurna
    $xpath = new DOMXPath($dom);

    // Query XPath untuk menemukan setiap item komik di daftar "Project" terbaru
    $nodes = $xpath->query("//div[contains(@class, 'bixbox') and .//h3/span[text()='Project']]//div[contains(@class, 'utao')]");

    foreach ($nodes as $node) {
        $linkNode = $xpath->query(".//a", $node)->item(0);
        $url = $linkNode ? $linkNode->getAttribute('href') : '';

        $titleNode = $xpath->query(".//h4", $node)->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : 'Tanpa Judul';

        $imageNode = $xpath->query(".//img/@src", $node)->item(0);
        $thumbnail = $imageNode ? trim($imageNode->nodeValue) : '';

        if ($url && $title && $thumbnail) {
            $comics[] = [
                'title' => $title,
                'url' => $url,
                'thumbnail' => $thumbnail,
            ];
        }
    }
    return $comics;
}

/**
 * Mengambil detail lengkap sebuah komik dari URL halamannya.
 *
 * @param string $url URL halaman detail komik.
 * @return array|null Detail komik atau null jika gagal.
 */
function get_comic_detail(string $url): ?array
{
    $html = fetch_page_content($url);
    if (!$html) {
        return null;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Mengambil data utama
    $title = trim($xpath->query("//h1[@class='entry-title']")->item(0)->textContent ?? 'Tanpa Judul');
    $image = trim($xpath->query("//div[@class='thumb']//img/@src")->item(0)->nodeValue ?? '');
    $synopsis = trim($xpath->query("//div[@class='entry-content']")->item(0)->textContent ?? 'Tidak ada sinopsis.');

    // Mengambil metadata (Status, Author, dll)
    $details = [];
    $detailNodes = $xpath->query("//div[@class='spe']/span");
    foreach ($detailNodes as $index => $node) {
        $key = strtolower(trim(str_replace(':', '', $node->textContent)));
        if ($index % 2 == 0 && isset($detailNodes[$index + 1])) {
            $valueNode = $detailNodes[$index + 1];
            $details[$key] = trim($valueNode->textContent);
        }
    }
    
    // Mengambil genre
    $genres = [];
    $genreNodes = $xpath->query("//span[@class='mgen']//a");
    foreach ($genreNodes as $node) {
        $genres[] = trim($node->textContent);
    }

    // Mengambil daftar chapter
    $chapters = [];
    $chapterNodes = $xpath->query("//div[@id='chapterlist']//li");
    foreach ($chapterNodes as $node) {
        $linkNode = $xpath->query(".//a", $node)->item(0);
        $chapterUrl = $linkNode ? $linkNode->getAttribute('href') : '';
        
        $chapterTitle = trim($xpath->query(".//span[@class='chapternum']", $node)->item(0)->textContent ?? 'N/A');
        $releaseDate = trim($xpath->query(".//span[@class='chapterdate']", $node)->item(0)->textContent ?? 'N/A');
        
        if ($chapterUrl && $chapterTitle) {
            $chapters[] = [
                'title' => $chapterTitle,
                'url' => $chapterUrl,
                'date' => $releaseDate,
            ];
        }
    }

    return [
        'title' => $title,
        'image' => $image,
        'author' => $details['author'] ?? 'N/A',
        'status' => $details['status'] ?? 'N/A',
        'genres' => $genres,
        'synopsis' => $synopsis,
        'chapters' => $chapters,
    ];
}

/**
 * Mengambil semua URL gambar dari halaman chapter.
 *
 * @param string $url URL halaman chapter.
 * @return array Daftar URL gambar.
 */
function get_chapter_images(string $url): array
{
    $html = fetch_page_content($url);
    if (!$html) {
        return [];
    }

    $images = [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Query XPath untuk menemukan semua gambar di dalam area baca
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

header('Content-Type: text/plain; charset=utf-8');

// 1. Mengambil daftar komik terbaru
echo "======================================\n";
echo "  MENGAMBIL DAFTAR KOMIK TERBARU\n";
echo "======================================\n";
$latestComics = get_latest_comics();
if (!empty($latestComics)) {
    $firstComic = $latestComics[0];
    print_r(array_slice($latestComics, 0, 3)); // Tampilkan 3 komik pertama

    // 2. Mengambil detail komik pertama
    echo "\n======================================\n";
    echo "  MENGAMBIL DETAIL KOMIK: {$firstComic['title']}\n";
    echo "======================================\n";
    $comicDetail = get_comic_detail($firstComic['url']);
    if ($comicDetail) {
        // Tampilkan beberapa detail saja agar tidak terlalu panjang
        echo "Judul: " . $comicDetail['title'] . "\n";
        echo "Status: " . $comicDetail['status'] . "\n";
        echo "Jumlah Chapter: " . count($comicDetail['chapters']) . "\n";
        print_r(array_slice($comicDetail['chapters'], 0, 2)); // Tampilkan 2 chapter pertama

        // 3. Mengambil gambar dari chapter pertama
        if (!empty($comicDetail['chapters'])) {
            $firstChapter = $comicDetail['chapters'][0];
            echo "\n======================================\n";
            echo "  MENGAMBIL GAMBAR CHAPTER: {$firstChapter['title']}\n";
            echo "======================================\n";
            $chapterImages = get_chapter_images($firstChapter['url']);
            if (!empty($chapterImages)) {
                echo "Ditemukan " . count($chapterImages) . " gambar.\n";
                print_r(array_slice($chapterImages, 0, 3)); // Tampilkan URL 3 gambar pertama
            } else {
                echo "Gagal mengambil gambar chapter.\n";
            }
        }
    } else {
        echo "Gagal mengambil detail komik.\n";
    }
} else {
    echo "Gagal mengambil daftar komik terbaru.\n";
}

*/
?>

