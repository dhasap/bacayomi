/**
 * assets/script.js
 * File JavaScript untuk interaksi di proyek Bacayomi.
 * Dibuat dengan Vanilla JS agar ringan dan cepat.
 */

// Menjalankan semua skrip setelah dokumen HTML selesai dimuat.
document.addEventListener('DOMContentLoaded', () => {

    // Inisialisasi fitur berdasarkan halaman yang sedang dibuka.
    // Ini untuk mencegah error jika elemen tidak ditemukan.
    if (document.querySelector('.source-selector')) {
        initSourceSelector();
        initLoadingState();
    }
    if (document.querySelector('.reader-images')) {
        initImageModal();
    }
    if (document.querySelector('.chapter-list')) {
        initChapterToggle();
    }

    // Fitur ini berjalan di semua halaman
    initScrollToTop();
});

/**
 * Fitur 1: Loading State saat ganti sumber
 * Menampilkan indikator loading saat form sumber dikirim.
 */
function initLoadingState() {
    const sourceForm = document.querySelector('.source-selector form');
    const loadingIndicator = document.getElementById('loading-indicator');
    const mainContent = document.querySelector('main');

    if (sourceForm && loadingIndicator && mainContent) {
        sourceForm.addEventListener('submit', () => {
            mainContent.style.display = 'none'; // Sembunyikan konten utama
            loadingIndicator.style.display = 'flex'; // Tampilkan loading
        });
    }
}

/**
 * Fitur 2: Tombol Scroll to Top
 * Menampilkan tombol untuk kembali ke atas halaman jika sudah scroll ke bawah.
 */
function initScrollToTop() {
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');
    if (!scrollToTopBtn) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollToTopBtn.classList.add('show');
        } else {
            scrollToTopBtn.classList.remove('show');
        }
    });

    scrollToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth' // Efek scroll halus
        });
    });
}

/**
 * Fitur 3: Auto-submit dropdown sumber komik
 * Otomatis reload halaman saat pengguna mengganti pilihan sumber.
 */
function initSourceSelector() {
    const sourceSelect = document.getElementById('source');
    if (sourceSelect) {
        sourceSelect.addEventListener('change', () => {
            sourceSelect.form.submit();
        });
    }
}

/**
 * Fitur 4: Modal Zoom Gambar di Halaman Reader
 * Memperbesar gambar chapter saat diklik.
 */
function initImageModal() {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const closeBtn = document.querySelector('.modal .close');
    const readerImages = document.querySelectorAll('.reader-images img');

    if (!modal || !modalImage || !closeBtn) return;

    readerImages.forEach(img => {
        img.addEventListener('click', () => {
            modal.style.display = 'flex';
            modalImage.src = img.src;
        });
    });

    const closeModal = () => {
        modal.style.display = 'none';
    };

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        // Tutup modal jika klik di area luar gambar
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Tutup modal dengan tombol Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && modal.style.display === 'flex') {
            closeModal();
        }
    });
}

/**
 * Fitur 5: Collapse/Expand Daftar Chapter
 * Menyembunyikan/menampilkan daftar chapter jika terlalu panjang.
 */
function initChapterToggle() {
    const toggleBtn = document.getElementById('chapterToggleBtn');
    const chapterList = document.querySelector('.chapter-list');

    if (!toggleBtn || !chapterList) return;

    // Hanya tampilkan tombol jika chapternya banyak
    if (chapterList.querySelectorAll('li').length > 15) {
        toggleBtn.style.display = 'inline-block';
        chapterList.classList.add('collapsed'); // Defaultnya diciutkan
    }

    toggleBtn.addEventListener('click', () => {
        chapterList.classList.toggle('collapsed');
        if (chapterList.classList.contains('collapsed')) {
            toggleBtn.textContent = 'Tampilkan Semua Chapter';
        } else {
            toggleBtn.textContent = 'Ciutkan Daftar';
        }
    });
}

