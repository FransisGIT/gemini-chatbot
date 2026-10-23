<?php

namespace App\Exports;

use App\Models\MautResult;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border as BorderStyle;

class HasilKinerjaExport implements WithEvents
{
    use RegistersEventListeners;

    const C_HEADER_DARK = 'FF1E3A5F';
    const C_HEADER_MED  = 'FF2563EB';
    const C_SECTION_BG  = 'FFEFF6FF';
    const C_WHITE       = 'FFFFFFFF';
    const C_MUTED       = 'FF64748B';
    const C_TEXT        = 'FF1E293B';

    public function __construct(protected Collection $riwayatHasil) {}

    public static function afterSheet(AfterSheet $event): void
    {
        $event->getConcernable()->buildSheet($event->sheet->getDelegate());
    }

    private function buildSheet(Worksheet $ws): void
    {
        $ws->getParent()->getActiveSheet()->setTitle('Hasil Kinerja MAUT');
        $ws->freezePane('A4');

        // ── Judul utama ───────────────────────────────────────────────
        $ws->mergeCells('A1:H1');
        $ws->getCell('A1')->setValue('RIWAYAT PERHITUNGAN MAUT');
        $ws->getStyle('A1:H1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => self::C_WHITE], 'name' => 'Arial'],
            'fill'      => $this->solidFill(self::C_HEADER_DARK),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(32);

        $ws->mergeCells('A2:H2');
        $ws->getStyle('A2:H2')->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['argb' => self::C_MUTED], 'italic' => true, 'name' => 'Arial'],
            'fill'      => $this->solidFill('FFF0F9FF'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(2)->setRowHeight(18);
        $ws->getRowDimension(3)->setRowHeight(10);

        $row = 4;

        foreach ($this->riwayatHasil as $indeks => $hasil) {
            $noPerhitungan = $this->riwayatHasil->count() - $indeks;
            $kriteria      = $hasil->criteria_snapshot;
            $alternatif    = $hasil->alternatives_snapshot;
            $matrix        = $hasil->normalized_matrix;
            $ranking       = $hasil->ranking;
            $colEnd        = $this->colLetter(max(count($kriteria) + 1, 4));

            // ── Banner perhitungan ────────────────────────────────────
            $ws->mergeCells("A{$row}:{$colEnd}{$row}");
            $ws->getCell("A{$row}")->setValue(
                "  Perhitungan #{$noPerhitungan}   |   " . $hasil->created_at->format('d M Y, H:i')
            );
            $ws->getStyle("A{$row}:{$colEnd}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => self::C_WHITE], 'name' => 'Arial'],
                'fill'      => $this->solidFill(self::C_HEADER_MED),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $ws->getRowDimension($row)->setRowHeight(26);
            $row++;

            // ── Label normalisasi ─────────────────────────────────────
            $ws->mergeCells("A{$row}:{$colEnd}{$row}");
            $ws->getCell("A{$row}")->setValue('  Hasil Normalisasi');
            $ws->getStyle("A{$row}:{$colEnd}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_HEADER_MED], 'name' => 'Arial'],
                'fill'      => $this->solidFill(self::C_SECTION_BG),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $ws->getRowDimension($row)->setRowHeight(20);
            $row++;

            // ── Header tabel normalisasi ──────────────────────────────
            $this->headerCell($ws, $row, 1, 'Alternatif');
            foreach ($kriteria as $ci => $k) {
                $this->headerCell($ws, $row, $ci + 2, $k['name']);
            }
            $ws->getRowDimension($row)->setRowHeight(30);
            $row++;

            // ── Data normalisasi ──────────────────────────────────────
            foreach ($alternatif as $ai => $alt) {
                $c = $ws->getCellByColumnAndRow(1, $row);
                $c->setValue($alt['name']);
                $ws->getStyleByColumnAndRow(1, $row)->applyFromArray([
                    'font'      => ['bold' => true, 'name' => 'Arial', 'color' => ['argb' => self::C_TEXT]],
                    'fill'      => $this->solidFill(self::C_WHITE),
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => $this->thinBorders(),
                ]);

                foreach ($kriteria as $ci => $_k) {
                    $val  = (float) ($matrix[$ai][$ci] ?? 0);
                    $cell = $ws->getCellByColumnAndRow($ci + 2, $row);
                    $cell->setValue($val);
                    $cell->getStyle()->getNumberFormat()->setFormatCode('0.000');
                    $ws->getStyleByColumnAndRow($ci + 2, $row)->applyFromArray([
                        'font'      => ['name' => 'Arial', 'color' => ['argb' => self::C_TEXT]],
                        'fill'      => $this->solidFill(self::C_WHITE),
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => $this->thinBorders(),
                    ]);
                }

                $ws->getRowDimension($row)->setRowHeight(18);
                $row++;
            }

            $row++; // spacer

            // ── Label ranking ─────────────────────────────────────────
            $ws->mergeCells("A{$row}:{$colEnd}{$row}");
            $ws->getCell("A{$row}")->setValue('  Ranking Alternatif');
            $ws->getStyle("A{$row}:{$colEnd}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_HEADER_MED], 'name' => 'Arial'],
                'fill'      => $this->solidFill(self::C_SECTION_BG),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $ws->getRowDimension($row)->setRowHeight(20);
            $row++;

            // ── Header tabel ranking ──────────────────────────────────
            foreach (['Peringkat', 'Alternatif', 'Skor'] as $ci => $h) {
                $this->headerCell($ws, $row, $ci + 1, $h);
            }
            $ws->getRowDimension($row)->setRowHeight(26);
            $row++;

            // ── Data ranking ──────────────────────────────────────────
            foreach ($ranking as $ri => $entri) {
                $vals   = [$ri + 1, $entri['name'], (float) $entri['total']];
                $aligns = [
                    Alignment::HORIZONTAL_CENTER,
                    Alignment::HORIZONTAL_LEFT,
                    Alignment::HORIZONTAL_CENTER,
                ];

                foreach ($vals as $ci => $val) {
                    $c = $ws->getCellByColumnAndRow($ci + 1, $row);
                    $c->setValue($val);
                    if ($ci === 2) {
                        $c->getStyle()->getNumberFormat()->setFormatCode('0.000');
                    }
                    $ws->getStyleByColumnAndRow($ci + 1, $row)->applyFromArray([
                        'font'      => ['name' => 'Arial', 'color' => ['argb' => self::C_TEXT]],
                        'fill'      => $this->solidFill(self::C_WHITE),
                        'alignment' => ['horizontal' => $aligns[$ci], 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => $this->thinBorders(),
                    ]);
                }

                $ws->getRowDimension($row)->setRowHeight(20);
                $row++;
            }

            $row += 2; // spacer antar perhitungan
        }

        // ── Lebar kolom ───────────────────────────────────────────────
        $ws->getColumnDimension('A')->setWidth(22);
        $ws->getColumnDimension('B')->setWidth(20);
        foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $ws->getColumnDimension($col)->setWidth(14);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function headerCell(Worksheet $ws, int $row, int $col, string $text): void
    {
        $ws->getCellByColumnAndRow($col, $row)->setValue($text);
        $ws->getStyleByColumnAndRow($col, $row)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => self::C_WHITE], 'name' => 'Arial'],
            'fill'      => $this->solidFill(self::C_HEADER_DARK),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => $this->thinBorders(),
        ]);
    }

    private function solidFill(string $argb): array
    {
        return ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $argb]];
    }

    private function thinBorders(): array
    {
        $side = ['style' => BorderStyle::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']];
        return ['left' => $side, 'right' => $side, 'top' => $side, 'bottom' => $side];
    }

    private function colLetter(int $n): string
    {
        $letter = '';
        while ($n > 0) {
            $n--;
            $letter = chr(65 + ($n % 26)) . $letter;
            $n      = (int) ($n / 26);
        }
        return $letter;
    }
}
