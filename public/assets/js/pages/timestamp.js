// Ambil locale dari Laravel
const LOCALE = window.APP_LOCALE || 'id';

// Elemen DOM
const tanggalElement = document.getElementById('tanggal');
const jamElement = document.getElementById('jam');

// Data hari dan bulan untuk multi bahasa
const DATA_LOCALE = {
    id: {
        days: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        months: [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ],
    },
    en: {
        days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        months: [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ],
    },
};

// Gunakan sesuai locale aktif
const DAYS = DATA_LOCALE[LOCALE]?.days || DATA_LOCALE.id.days;
const MONTHS = DATA_LOCALE[LOCALE]?.months || DATA_LOCALE.id.months;

// Fungsi utilitas
const padZero = (angka) => (angka < 10 ? '0' : '') + angka;

// Fungsi utama
function updateWaktu() {
    const now = new Date();

    const hari = now.getDay();
    const tanggal = now.getDate();
    const bulan = now.getMonth();
    const tahun = now.getFullYear();

    const jam = padZero(now.getHours());
    const menit = padZero(now.getMinutes());
    const detik = padZero(now.getSeconds());

    const formatTanggal = `${DAYS[hari]}, ${tanggal} ${MONTHS[bulan]} ${tahun}`;
    const formatJam = `${jam}:${menit}:${detik}`;

    // Simpan ke localStorage (opsional)
    localStorage.setItem('tanggal', formatTanggal);
    localStorage.setItem('jam', formatJam);

    // Update DOM
    tanggalElement.textContent = formatTanggal;
    jamElement.textContent = formatJam;
}

// Inisialisasi
document.addEventListener('DOMContentLoaded', () => {
    const storedTanggal = localStorage.getItem('tanggal');
    const storedJam = localStorage.getItem('jam');

    if (storedTanggal && storedJam) {
        tanggalElement.textContent = storedTanggal;
        jamElement.textContent = storedJam;
    }

    updateWaktu();
    setInterval(updateWaktu, 1000);
});
