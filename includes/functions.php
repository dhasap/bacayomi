<?php
/**
 * File: functions.php
 * Deskripsi: Kumpulan fungsi inti untuk proyek Bacayomi.
 * Termasuk sistem pemanggil scraper yang modular.
 */

/**
 * Peta Konfigurasi Scraper.
 * Ini adalah pusat kendali untuk semua sumber komik.
 * Untuk menambah sumber baru, cukup tambahkan entri di sini.
 *
 * Format:
 * 'nama_sumber' => [
 * 'file'   => 'path/ke/file/scraper.php',
 * 'latest' => 'nama_fungsi_untuk_ambil_komik_terbaru',
 * 'detail' => 'nama_fungsi_untuk_ambil_detail_komik',
 * 'images' => 'nama_fungsi_untuk_ambil_gambar_chapter',
 * ]
 */
$SCRAPER_CONFIG = [
    'komikcast' => [
        'file'   => 'scraper/komikcast.php',
        'latest' => 'getKomikList',
        'detail' => 'getChapter', // Di komikcast, ini mengambil daftar chapter
        'images' => 'getChapterImages',
    ],
    'shinigami' => [
        'file'   => 'scraper/shinigami.php',
        'latest' => 'get_latest_comics',
        'detail' => 'get_comic_detail',
        'images' => 'get_chapter_images',
    ],
    'kiryuu' => [
        'file'   => 'scraper/kiryuu.php',
        'latest' => 'get_latest_comics',
        'detail' => 'get_comic_detail',
        'images' => 'get_chapter_images',
    ],
    'sektekomik' => [
        'file'   => 'scraper/sektekomik.php',
        'latest' => 'get_latest_comics',
        'detail' => 'get_comic_detail',
        'images' => 'get_chapter_images',
    ],
    // Tambahkan scraper baru di sini di masa depan
];

/**
 * Fungsi internal untuk memanggil fungsi scraper secara aman.
 *
 * @param string $sourceName Nama sumber (e.g., 'komikcast').
 * @param string $action     Aksi yang diinginkan ('latest', 'detail', 'images').
 * @param array  $params     Parameter yang akan diteruskan ke fungsi scraper.
 * @return mixed|null        Hasil dari fungsi scraper, atau null jika gagal.
 */
function call_scraper_function(string $sourceName, string $action, array $params = [])
{
    global $SCRAPER_CONFIG;

    // 1. Cek apakah sumber terdaftar di konfigurasi
    if (!isset($SCRAPER_CONFIG[$sourceName])) {
        // error_log("Sumber tidak dikenal: " . $sourceName);
        return null;
    }

    $config = $SCRAPER_CONFIG[$sourceName];
    $filePath = $config['file'];
    $functionName = $config[$action] ?? null;

    // 2. Cek apakah file scraper ada
    if (!file_exists($filePath)) {
        // error_log("File scraper tidak ditemukan: " . $filePath);
        return null;
    }

    // Muat file scraper hanya sekali
    require_once $filePath;

    // 3. Cek apakah fungsi yang diminta ada di dalam file scraper
    if (!$functionName || !function_exists($functionName)) {
        // error_log("Fungsi tidak ditemukan: " . $functionName);
        return null;
    }

    // 4. Panggil fungsi dengan parameter yang diberikan
    return call_user_func_array($functionName, $params);
}

/**
 * Fungsi publik untuk mengambil daftar komik terbaru dari sumber tertentu.
 * Ini adalah implementasi dari `loadSource()` yang Anda minta.
 *
 * @param string $sourceName Nama sumber (e.g., 'komikcast').
 * @return array             Daftar komik terbaru, atau array kosong jika gagal.
 */
function get_latest_comics_from_source(string $sourceName): array
{
    $result = call_scraper_function($sourceName, 'latest');
    return is_array($result) ? $result : [];
}

/**
 * Fungsi publik untuk mengambil detail sebuah komik dari sumber tertentu.
 *
 * @param string $sourceName Nama sumber.
 * @param string $urlOrId    URL atau ID unik dari komik.
 * @return array|null        Detail komik, atau null jika gagal.
 */
function get_comic_detail_from_source(string $sourceName, string $urlOrId): ?array
{
    return call_scraper_function($sourceName, 'detail', [$urlOrId]);
}

/**
 * Fungsi publik untuk mengambil gambar-gambar dari sebuah chapter.
 *
 * @param string $sourceName Nama sumber.
 * @param string $chapterUrl URL dari chapter.
 * @return array             Daftar URL gambar, atau array kosong jika gagal.
 */
function get_chapter_images_from_source(string $sourceName, string $chapterUrl): array
{
    $result = call_scraper_function($sourceName, 'images', [$chapterUrl]);
    return is_array($result) ? $result : [];
}

?>

