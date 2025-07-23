<?php
/**
 * File: api/index.php
 * Deskripsi: Endpoint API terpusat untuk proyek Bacayomi.
 * Bertugas menerima request, memanggil scraper yang sesuai, dan mengembalikan hasil dalam format JSON.
 *
 * Contoh Penggunaan:
 * - /api/?source=kiryuu&type=latest
 * - /api/?source=komikcast&type=detail&url=https://komikcast.lol/komik/the-beginning-after-the-end/
 * - /api/?source=shinigami&type=chapter&url=https://shinigamitoon.com/series/solo-leveling/chapter-01/
 */

// 1. Set header utama untuk semua response
// Ini memastikan output selalu dianggap sebagai JSON oleh client.
header('Content-Type: application/json');

// 2. Muat file fungsi inti
// Path menggunakan '../' karena file ini berada satu level di dalam folder 'api'.
require_once '../includes/functions.php';

/**
 * Fungsi helper untuk mengirim response JSON yang terstruktur dan menghentikan eksekusi.
 *
 * @param array $data Data yang akan di-encode ke JSON.
 * @param int $status_code Kode status HTTP (e.g., 200, 400, 404).
 */
function send_json_response(array $data, int $status_code = 200) {
    http_response_code($status_code);
    // Menggunakan flag JSON_PRETTY_PRINT dan JSON_UNESCAPED_SLASHES sesuai permintaan.
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit; // Menghentikan skrip agar tidak ada output lain yang tercetak.
}

// 3. Ambil dan validasi parameter dari URL
$source = $_GET['source'] ?? null;
$type = $_GET['type'] ?? null;
$url = $_GET['url'] ?? null;

if (!$source) {
    send_json_response(['status' => 'error', 'message' => 'Parameter "source" dibutuhkan.'], 400);
}

// Validasi apakah source terdaftar di konfigurasi
if (!isset($SCRAPER_CONFIG[$source])) {
    send_json_response(['status' => 'error', 'message' => "Source '{$source}' tidak dikenal atau tidak didukung."], 400);
}

$allowed_types = ['latest', 'detail', 'chapter'];
if (!$type || !in_array($type, $allowed_types)) {
    send_json_response(['status' => 'error', 'message' => 'Parameter "type" tidak valid. Gunakan: ' . implode(', ', $allowed_types) . '.'], 400);
}

// Validasi URL jika tipenya 'detail' atau 'chapter'
if (($type === 'detail' || $type === 'chapter') && !$url) {
    send_json_response(['status' => 'error', 'message' => 'Parameter "url" dibutuhkan untuk tipe "' . $type . '".'], 400);
}

// 4. Proses request berdasarkan 'type'
$result = null;

try {
    switch ($type) {
        case 'latest':
            $result = get_latest_comics_from_source($source);
            break;
        
        case 'detail':
            $result = get_comic_detail_from_source($source, $url);
            break;
        
        case 'chapter':
            $result = get_chapter_images_from_source($source, $url);
            break;
    }
} catch (Exception $e) {
    // Menangkap error tak terduga dari scraper
    send_json_response(['status' => 'error', 'message' => 'Terjadi kesalahan internal pada server.'], 500);
}


// 5. Kirim response akhir
if ($result !== null) {
    // Jika scraper berhasil (meskipun hasilnya mungkin array kosong)
    send_json_response([
        'status' => 'success',
        'source' => $source,
        'data' => $result
    ], 200);
} else {
    // Jika scraper gagal atau tidak mengembalikan data (null)
    send_json_response([
        'status' => 'error',
        'message' => 'Gagal mengambil data. URL mungkin tidak valid atau scraper mengalami masalah.'
    ], 404);
}

