<?php

namespace App\Exports;

use App\Models\MautResult;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HasilKinerjaSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected MautResult $hasil,
        protected int $nomorPerhitungan
    ) {}

    public function title(): string
    {
        return 'Perhitungan #' . $this->nomorPerhitungan;
    }

    public function array(): array
    {
        $baris = [];

        // ── Header info ──
        $baris[] = ['Riwayat Perhitungan MAUT'];
        $baris[] = ['Perhitungan #' . $this->nomorPerhitungan, $this->hasil->created_at->format('d M Y, H:i')];
        $baris[] = [];

        // ── Tabel Normalisasi ──
        $baris[] = ['HASIL NORMALISASI'];

        $headerNormalisasi = ['Alternatif'];
        foreach ($this->hasil->criteria_snapshot as $k) {
            $headerNormalisasi[] = $k['name'];
        }
        $baris[] = $headerNormalisasi;

        foreach ($this->hasil->alternatives_snapshot as $iAlt => $alt) {
            $barisnormalisasi = [$alt['name']];
            foreach ($this->hasil->criteria_snapshot as $iKrit => $k) {
                $barisnormalisasi[] = number_format(
                    $this->hasil->normalized_matrix[$iAlt][$iKrit] ?? 0,
                    3
                );
            }
            $baris[] = $barisnormalisasi;
        }

        $baris[] = [];

        // ── Tabel Ranking ──
        $baris[] = ['RANKING ALTERNATIF'];
        $baris[] = ['Peringkat', 'Alternatif', 'Skor', 'Keterangan'];

        foreach ($this->hasil->ranking as $peringkat => $entri) {
            $baris[] = [
                $peringkat + 1,
                $entri['name'],
                number_format($entri['total'], 3),
                $entri['performance'] ?? '-',
            ];
        }

        return $baris;
    }

    public function styles(Worksheet $sheet): array
    {
        $jumlahKriteria = count($this->hasil->criteria_snapshot);
        $kolomTerakhir  = $this->indexToLetter($jumlahKriteria + 1); // +1 untuk kolom Alternatif

        return [
            // Judul utama
            1 => [
                'font' => ['bold' => true, 'size' => 14],
            ],
            // Baris info tanggal
            2 => [
                'font' => ['bold' => true],
            ],
            // Label section normalisasi (baris 4)
            4 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2563EB'],
                ],
            ],
            // Header tabel normalisasi (baris 5)
            5 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFDBEAFE'],
                ],
            ],
        ];
    }

    private function indexToLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index  = (int) ($index / 26);
        }
        return $letter;
    }
}
