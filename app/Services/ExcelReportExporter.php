<?php

namespace App\Services;

use App\Models\RawVisit;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ExcelReportExporter
{
    public static function exportFullOutput($month, $year, $filename = null)
    {
        $query = RawVisit::query();
        if ($month) {
            $query->whereMonth('tgl_berobat', $month);
        }
        if ($year) {
            $query->whereYear('tgl_berobat', $year);
        }

        $visits = $query->orderBy('tgl_berobat')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();

        // Standard colors
        $darkGreenHeader = '2A6A2A';
        $lightGreenTotal = 'E8F5E9';
        $borderColor     = 'D0D0D0';

        // ----------------------------------------------------
        // SHEET 1: Lap. kunjungan pasien
        // ----------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Lap. kunjungan pasien');
        $sheet1->setShowGridLines(true);

        // Document Header (Kop Surat) - All in Column A
        $sheet1->setCellValue('A1', 'MARKAS BESAR TNI ANGKATAN DARAT');
        $sheet1->setCellValue('A2', 'RSPAD GATOT SOEBROTO');
        $sheet1->setCellValue('A3', 'Jl. Abdul Rahman Saleh No. 24, Jakarta Pusat');
        $sheet1->setCellValue('A4', 'Telp : (021) 3441008, 3840702, Fax : (021) 3520619');

        $sheet1->getStyle('A1:A2')->getFont()->setBold(true)->setSize(11);
        $sheet1->getStyle('A3:A4')->getFont()->setSize(9.5);

        // Sub-header Metadata
        $sheet1->setCellValue('A6', 'LAPORAN KUNJUNGAN PASIEN');
        $sheet1->getStyle('A6')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E561E'));

        $sheet1->setCellValue('A7', 'LOKASI');       $sheet1->setCellValue('B7', ': RSPAD');
        $sheet1->setCellValue('A8', 'POLIKLINIK');   $sheet1->setCellValue('B8', ': Semua Poli');
        $sheet1->setCellValue('A9', 'JENIS RAWAT');  $sheet1->setCellValue('B9', ': WATLAN');
        $sheet1->setCellValue('A10', 'STATUS REGIS'); $sheet1->setCellValue('B10', ': OPEN');

        $mStr = sprintf('%02d', $month ?: date('m'));
        $yStr = $year ?: date('Y');
        $lastDay = date('t', strtotime("{$yStr}-{$mStr}-01"));
        $sheet1->setCellValue('A11', 'TANGGAL');
        $sheet1->setCellValue('B11', ": 01/{$mStr}/{$yStr} s/d {$lastDay}/{$mStr}/{$yStr}");

        $sheet1->getStyle('A7:A11')->getFont()->setBold(true)->setSize(10);
        $sheet1->getStyle('B7:B11')->getFont()->setSize(10);

        // Row 13: Table Headers
        $headers1 = [
            'NO', 'NO RM', 'NAMA PASIEN', 'TANGGAL LAHIR', 'USIA', 'NO. TELP', 'NO. PONSEL',
            'POLI', 'DOKTER', 'TANGGAL', 'JAM', 'NO SEP', 'NO PESERTA', 'TYPE PASIEN',
            'JENIS RAWAT', 'JENIS PEMBAYARAN', 'KELOMPOK', 'PANGKAT', 'NRP', 'KELAMIN',
            'AGAMA', 'PENDIDIKAN', 'KESATUAN', 'ANGKATAN', 'HUBUNGAN KELUARGA', 'ALAMAT',
            'KODE', 'DIAGNOSA AWAL', 'KODE', 'DIAGNOSA AKHIR', 'STATUS'
        ];

        foreach ($headers1 as $colIdx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet1->setCellValue("{$colLetter}13", $hText);
        }

        $sheet1->getRowDimension(13)->setRowHeight(28);

        $headerStyle1 = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $darkGreenHeader]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ];
        $sheet1->getStyle('A13:AE13')->applyFromArray($headerStyle1);

        // Column Widths for Sheet 1 (Column A width = 16 to fit POLIKLINIK, JENIS RAWAT, STATUS REGIS, TANGGAL cleanly)
        $widths1 = [
            'A' => 16,  'B' => 14,  'C' => 28,  'D' => 16,  'E' => 8,
            'F' => 18,  'G' => 18,  'H' => 24,  'I' => 28,  'J' => 14,
            'K' => 10,  'L' => 24,  'M' => 22,  'N' => 16,  'O' => 16,
            'P' => 24,  'Q' => 28,  'R' => 14,  'S' => 20,  'T' => 10,
            'U' => 12,  'V' => 14,  'W' => 24,  'X' => 18,  'Y' => 22,
            'Z' => 32,  'AA' => 10, 'AB' => 32, 'AC' => 10, 'AD' => 32, 'AE' => 14
        ];
        foreach ($widths1 as $col => $w) {
            $sheet1->getColumnDimension($col)->setWidth($w);
        }

        // Data Rows Sheet 1
        $r = 14;
        foreach ($visits as $idx => $v) {
            $sheet1->setCellValue("A{$r}", $idx + 1);
            $sheet1->setCellValueExplicit("B{$r}", (string)$v->no_rm, DataType::TYPE_STRING);
            $sheet1->setCellValue("C{$r}", $v->nama_pasien);
            $sheet1->setCellValue("D{$r}", $v->tgl_lahir);
            $sheet1->setCellValue("E{$r}", $v->umur);
            $sheet1->setCellValueExplicit("F{$r}", (string)$v->no_telp, DataType::TYPE_STRING);
            $sheet1->setCellValueExplicit("G{$r}", (string)$v->no_hp, DataType::TYPE_STRING);
            $sheet1->setCellValue("H{$r}", $v->poliklinik);
            $sheet1->setCellValue("I{$r}", $v->dokter);
            $sheet1->setCellValue("J{$r}", $v->tgl_berobat);
            $sheet1->setCellValue("K{$r}", $v->jam);
            $sheet1->setCellValueExplicit("L{$r}", (string)$v->no_sep, DataType::TYPE_STRING);
            $sheet1->setCellValueExplicit("M{$r}", (string)$v->no_bpjs, DataType::TYPE_STRING);
            $sheet1->setCellValue("N{$r}", $v->status_pasien);
            $sheet1->setCellValue("O{$r}", $v->jenis_rawat);
            $sheet1->setCellValue("P{$r}", $v->jenis_penjamin);
            $sheet1->setCellValue("Q{$r}", $v->kelompok);
            $sheet1->setCellValue("R{$r}", $v->pangkat);
            $sheet1->setCellValueExplicit("S{$r}", (string)$v->nip_nrp_pasien, DataType::TYPE_STRING);
            $sheet1->setCellValue("T{$r}", $v->gender);
            $sheet1->setCellValue("U{$r}", $v->agama);
            $sheet1->setCellValue("V{$r}", $v->pendidikan);
            $sheet1->setCellValue("W{$r}", $v->kesatuan);
            $sheet1->setCellValue("X{$r}", $v->instansi);
            $sheet1->setCellValue("Y{$r}", $v->kategori);
            $sheet1->setCellValue("Z{$r}", $v->alamat);
            $sheet1->setCellValueExplicit("AA{$r}", (string)$v->icd10_utama, DataType::TYPE_STRING);
            $sheet1->setCellValue("AB{$r}", $v->deskripsi_icd10_utama);
            $sheet1->setCellValueExplicit("AC{$r}", (string)$v->icd10_sekunder, DataType::TYPE_STRING);
            $sheet1->setCellValue("AD{$r}", $v->deskripsi_icd10_sekunder);
            $sheet1->setCellValue("AE{$r}", $v->status_registrasi);

            $sheet1->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $lastRow1 = $r - 1;
        if ($lastRow1 >= 14) {
            $dataStyle1 = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => $borderColor]
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet1->getStyle("A14:AE{$lastRow1}")->applyFromArray($dataStyle1);

            $centerCols = ['A', 'B', 'D', 'E', 'J', 'K', 'T', 'U', 'AA', 'AC', 'AE'];
            foreach ($centerCols as $c) {
                $sheet1->getStyle("{$c}14:{$c}{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // ----------------------------------------------------
        // SHEET 2: rekap kunjungan
        // ----------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('rekap kunjungan');
        $sheet2->setShowGridLines(true);

        // Document Header Sheet 2
        $sheet2->setCellValue('A1', 'MARKAS BESAR TNI ANGKATAN DARAT');
        $sheet2->setCellValue('A2', 'RSPAD GATOT SOEBROTO');
        $sheet2->setCellValue('A3', 'Jl. Abdul Rahman Saleh No. 24, Jakarta Pusat');
        $sheet2->setCellValue('A4', 'Telp : (021) 3441008, 3840702, Fax : (021) 3520619');

        $sheet2->getStyle('A1:A2')->getFont()->setBold(true)->setSize(11);
        $sheet2->getStyle('A3:A4')->getFont()->setSize(9.5);

        $sheet2->setCellValue('A6', 'REKAP KUNJUNGAN');
        $sheet2->getStyle('A6')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E561E'));

        $sheet2->setCellValue('A7', 'LOKASI');       $sheet2->setCellValue('B7', ': RSPAD');
        $sheet2->setCellValue('A8', 'POLIKLINIK');   $sheet2->setCellValue('B8', ': Semua Poli');
        $sheet2->setCellValue('A9', 'JENIS RAWAT');  $sheet2->setCellValue('B9', ': WATLAN');
        $sheet2->setCellValue('A10', 'STATUS REGIS'); $sheet2->setCellValue('B10', ': OPEN');
        $sheet2->setCellValue('A11', 'TANGGAL');    $sheet2->setCellValue('B11', ": 01/{$mStr}/{$yStr} s/d {$lastDay}/{$mStr}/{$yStr}");

        $sheet2->getStyle('A7:A11')->getFont()->setBold(true)->setSize(10);
        $sheet2->getStyle('B7:B11')->getFont()->setSize(10);

        // Row 13 Headers Sheet 2
        $sheet2->setCellValue('A13', 'NO');
        $sheet2->setCellValue('B13', 'GOLONGAN PERSONIL');
        $sheet2->setCellValue('C13', 'JUMLAH PASIEN BARU');
        $sheet2->setCellValue('D13', 'JUMLAH PASIEN LAMA');
        $sheet2->setCellValue('E13', 'JUMLAH');

        $sheet2->getRowDimension(13)->setRowHeight(28);
        $sheet2->getStyle('A13:E13')->applyFromArray($headerStyle1);

        $widths2 = [
            'A' => 16,
            'B' => 32,
            'C' => 22,
            'D' => 22,
            'E' => 18
        ];
        foreach ($widths2 as $col => $w) {
            $sheet2->getColumnDimension($col)->setWidth($w);
        }

        // Group by Kelompok for Rekap Sheet
        $rekapGroup = [];
        foreach ($visits as $v) {
            $kel = $v->kelompok ?: 'LAIN-LAIN';
            if (!isset($rekapGroup[$kel])) {
                $rekapGroup[$kel] = ['baru' => 0, 'lama' => 0, 'total' => 0];
            }
            if ($v->status_pasien === 'Pasien Baru') {
                $rekapGroup[$kel]['baru']++;
            } else {
                $rekapGroup[$kel]['lama']++;
            }
            $rekapGroup[$kel]['total']++;
        }

        $r2 = 14;
        $no2 = 1;
        $totBaru = 0;
        $totLama = 0;

        foreach ($rekapGroup as $kelName => $counts) {
            $sheet2->setCellValue("A{$r2}", $no2++);
            $sheet2->setCellValue("B{$r2}", $kelName);
            $sheet2->setCellValue("C{$r2}", $counts['baru']);
            $sheet2->setCellValue("D{$r2}", $counts['lama']);
            $sheet2->setCellValue("E{$r2}", $counts['total']);
            $totBaru += $counts['baru'];
            $totLama += $counts['lama'];

            $sheet2->getRowDimension($r2)->setRowHeight(20);
            $r2++;
        }

        $lastDataRow2 = $r2 - 1;
        if ($lastDataRow2 >= 14) {
            $sheet2->getStyle("A14:E{$lastDataRow2}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => $borderColor]
                    ]
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheet2->getStyle("A14:A{$lastDataRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("C14:E{$lastDataRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Total Row Sheet 2
        $sheet2->setCellValue("A{$r2}", 'TOTAL');
        $sheet2->setCellValue("B{$r2}", '');
        $sheet2->setCellValue("C{$r2}", $totBaru);
        $sheet2->setCellValue("D{$r2}", $totLama);
        $sheet2->setCellValue("E{$r2}", $totBaru + $totLama);

        $sheet2->getRowDimension($r2)->setRowHeight(24);
        $sheet2->getStyle("A{$r2}:E{$r2}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $lightGreenTotal]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $darkGreenHeader]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => $darkGreenHeader]],
                'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]],
                'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]],
            ]
        ]);
        $sheet2->getStyle("A{$r2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle("C{$r2}:E{$r2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Download Response
        $writer = new Xlsx($spreadsheet);
        if (!$filename) {
            $filename = "Laporan_Kunjungan_RSPAD_{$month}_{$year}.xlsx";
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $writer->save('php://output');
        exit;
    }
}
