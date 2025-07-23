# Bacayomi 📚

Bacayomi adalah project web pembaca komik open-source berbasis PHP yang terinspirasi dari Tachiyomi. Tujuan utamanya adalah menyediakan platform ringan dan modular untuk membaca komik dari berbagai sumber eksternal (via scraper).

---

## 🚀 Fitur Utama

- Baca komik langsung dari web
- Sumber komik fleksibel (gonta-ganti scraper)
- Tampilan ringan & cocok buat mobile
- Bisa di-hosting di layanan gratis seperti InfinityFree

---

## 🏗️ Struktur Project

/htdocs │ ├── index.php          # Halaman utama ├── reader.php         # Halaman pembaca komik ├── scraper/           # Kumpulan scraper dari berbagai situs │   ├── komikcast.php │   └── mangaku.php ├── assets/ │   ├── style.css │   └── script.js └── includes/ └── functions.php

---

## ⚙️ Cara Pakai (Versi InfinityFree)

1. Upload file ke `htdocs` di InfinityFree
2. Buka domain (contoh: `https://bacayomi.rf.gd`)
3. Pilih komik dari sumber yang tersedia
4. Nikmati baca komik secara online 😎

---

## 🧩 Menambah Sumber Komik

- Tambahkan file PHP baru di folder `scraper/`
- Pastikan fungsi scraping mengikuti struktur standar `getKomikList()` dan `getChapter()`
- Update sistem pemanggilan scraper di `functions.php`

---

## 🛠 Status Project

> ⚠️ Project masih dalam tahap pengembangan awal.  
> Pull request & masukan dari komunitas sangat welcome!

---

## 👤 Developer

**Project Manager**: [Dhany](https://github.com/)  
**Assistant & Support**: Jono  
**Code Execution**: Jamal

---

## 📜 License

This project is open-sourced under the MIT License.
