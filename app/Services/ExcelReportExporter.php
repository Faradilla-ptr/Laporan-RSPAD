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
    public static function exportFullOutput($month, $year, $filename = null, $poli = null, $savePath = null)
    {
        $query = RawVisit::query();
        if ($month) {
            $query->whereMonth('tgl_berobat', $month);
        }
        if ($year) {
            $query->whereYear('tgl_berobat', $year);
        }
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $visits = $query->orderBy('tgl_berobat')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();

        // Standard colors
        $darkGreenHeader = '2A6A2A';
        $lightGreenTotal = 'E8F5E9';
        $borderColor     = 'D0D0D0';

        $mStr = sprintf('%02d', $month ?: date('m'));
        $yStr = $year ?: date('Y');
        $lastDay = date('t', strtotime("{$yStr}-{$mStr}-01"));
        $poliTitle = ($poli && $poli !== 'SEMUA') ? strtoupper($poli) : 'SEMUA POLI';

        // ----------------------------------------------------
        // 1. SHEET: JK (Jenis Kelamin - Pengunjung Unik)
        // ----------------------------------------------------
        $sheetJK = $spreadsheet->getActiveSheet();
        $sheetJK->setTitle('JK');
        $sheetJK->setShowGridLines(true);

        // Unique patients by RM
        $uniquePatients = [];
        foreach ($visits as $v) {
            if (!isset($uniquePatients[$v->no_rm])) {
                $uniquePatients[$v->no_rm] = $v;
            }
        }

        $jkCounts = ['L' => 0, 'P' => 0];
        foreach ($uniquePatients as $p) {
            $g = strtoupper(trim((string)$p->gender)) === 'P' ? 'P' : 'L';
            $jkCounts[$g]++;
        }

        $sheetJK->setCellValue('A3', 'Count of NO RM');
        $sheetJK->setCellValue('A4', 'KELAMIN');
        $sheetJK->setCellValue('B4', 'Total');
        $sheetJK->setCellValue('A5', 'L');
        $sheetJK->setCellValue('B5', $jkCounts['L']);
        $sheetJK->setCellValue('A6', 'P');
        $sheetJK->setCellValue('B6', $jkCounts['P']);
        $sheetJK->setCellValue('A8', 'Grand Total');
        $sheetJK->setCellValue('B8', count($uniquePatients));

        $sheetJK->getStyle('A4:B4')->getFont()->setBold(true);
        $sheetJK->getStyle('A8:B8')->getFont()->setBold(true);
        $sheetJK->getColumnDimension('A')->setWidth(18);
        $sheetJK->getColumnDimension('B')->setWidth(14);

        // ----------------------------------------------------
        // 2. SHEET: PIVOT P (Pivot Pengunjung Unik)
        // ----------------------------------------------------
        $sheetPivotP = $spreadsheet->createSheet();
        $sheetPivotP->setTitle('PIVOT P');
        $sheetPivotP->setShowGridLines(true);

        $sheetPivotP->setCellValue('A3', 'Count of NO RM');
        $sheetPivotP->setCellValue('A4', 'JENIS PEMBAYARAN');
        $sheetPivotP->setCellValue('B4', 'KELOMPOK');
        $sheetPivotP->setCellValue('C4', 'Total');
        $sheetPivotP->getStyle('A4:C4')->getFont()->setBold(true);

        $pivotPData = [];
        foreach ($uniquePatients as $p) {
            $pen = $p->jenis_penjamin ?: 'LAIN-LAIN';
            $kel = $p->kelompok ?: 'UMUM / TUNAI';
            if (!isset($pivotPData[$pen][$kel])) {
                $pivotPData[$pen][$kel] = 0;
            }
            $pivotPData[$pen][$kel]++;
        }

        $rpP = 5;
        $grandTotalP = 0;
        foreach ($pivotPData as $penName => $kelGroup) {
            $penTotal = 0;
            foreach ($kelGroup as $kelName => $cnt) {
                $sheetPivotP->setCellValue("A{$rpP}", $penName);
                $sheetPivotP->setCellValue("B{$rpP}", $kelName);
                $sheetPivotP->setCellValue("C{$rpP}", $cnt);
                $penTotal += $cnt;
                $rpP++;
            }
            // Subtotal row
            $sheetPivotP->setCellValue("A{$rpP}", "{$penName} Total");
            $sheetPivotP->setCellValue("C{$rpP}", $penTotal);
            $sheetPivotP->getStyle("A{$rpP}:C{$rpP}")->getFont()->setBold(true);
            $grandTotalP += $penTotal;
            $rpP++;
        }

        $sheetPivotP->setCellValue("A{$rpP}", 'Grand Total');
        $sheetPivotP->setCellValue("C{$rpP}", $grandTotalP);
        $sheetPivotP->getStyle("A{$rpP}:C{$rpP}")->getFont()->setBold(true);

        $sheetPivotP->getColumnDimension('A')->setWidth(26);
        $sheetPivotP->getColumnDimension('B')->setWidth(26);
        $sheetPivotP->getColumnDimension('C')->setWidth(14);

        // ----------------------------------------------------
        // 3. SHEET: P (Data Detail Pengunjung Unik)
        // ----------------------------------------------------
        $sheetP = $spreadsheet->createSheet();
        $sheetP->setTitle('P');
        $sheetP->setShowGridLines(true);

        $headersDetail = [
            'NO', 'NO RM', 'NAMA PASIEN', 'TANGGAL LAHIR', 'USIA', 'TYPE PASIEN',
            'NO. TELP', 'NO. PONSEL', 'POLI', 'DOKTER', 'TANGGAL', 'JAM', 'NO SEP', 'NO PESERTA',
            'JENIS RAWAT', 'JENIS PEMBAYARAN', 'KELOMPOK', 'PANGKAT', 'NRP', 'KELAMIN',
            'AGAMA', 'PENDIDIKAN', 'KESATUAN', 'ANGKATAN', 'HUBUNGAN KELUARGA', 'ALAMAT',
            'KODE', 'DIAGNOSA AWAL', 'KODE', 'DIAGNOSA AKHIR', 'STATUS'
        ];

        foreach ($headersDetail as $colIdx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetP->setCellValue("{$colLetter}1", $hText);
        }
        $sheetP->getStyle('A1:AE1')->getFont()->setBold(true);

        $rP = 2;
        $noP = 1;
        foreach ($uniquePatients as $v) {
            $sheetP->setCellValue("A{$rP}", $noP++);
            $sheetP->setCellValueExplicit("B{$rP}", (string)$v->no_rm, DataType::TYPE_STRING);
            $sheetP->setCellValue("C{$rP}", $v->nama_pasien);
            $sheetP->setCellValue("D{$rP}", $v->tgl_lahir);
            $sheetP->setCellValue("E{$rP}", $v->umur);
            $sheetP->setCellValue("F{$rP}", $v->status_pasien);
            $sheetP->setCellValueExplicit("G{$rP}", (string)$v->no_telp, DataType::TYPE_STRING);
            $sheetP->setCellValueExplicit("H{$rP}", (string)$v->no_hp, DataType::TYPE_STRING);
            $sheetP->setCellValue("I{$rP}", $v->poliklinik);
            $sheetP->setCellValue("J{$rP}", $v->dokter);
            $sheetP->setCellValue("K{$rP}", $v->tgl_berobat);
            $sheetP->setCellValue("L{$rP}", $v->jam);
            $sheetP->setCellValueExplicit("M{$rP}", (string)$v->no_sep, DataType::TYPE_STRING);
            $sheetP->setCellValueExplicit("N{$rP}", (string)$v->no_bpjs, DataType::TYPE_STRING);
            $sheetP->setCellValue("O{$rP}", $v->jenis_rawat);
            $sheetP->setCellValue("P{$rP}", $v->jenis_penjamin);
            $sheetP->setCellValue("Q{$rP}", $v->kelompok);
            $sheetP->setCellValue("R{$rP}", $v->pangkat);
            $sheetP->setCellValueExplicit("S{$rP}", (string)$v->nip_nrp_pasien, DataType::TYPE_STRING);
            $sheetP->setCellValue("T{$rP}", $v->gender);
            $sheetP->setCellValue("U{$rP}", $v->agama);
            $sheetP->setCellValue("V{$rP}", $v->pendidikan);
            $sheetP->setCellValue("W{$rP}", $v->kesatuan);
            $sheetP->setCellValue("X{$rP}", $v->instansi);
            $sheetP->setCellValue("Y{$rP}", $v->kategori);
            $sheetP->setCellValue("Z{$rP}", $v->alamat);
            $sheetP->setCellValueExplicit("AA{$rP}", (string)$v->icd10_utama, DataType::TYPE_STRING);
            $sheetP->setCellValue("AB{$rP}", $v->deskripsi_icd10_utama);
            $sheetP->setCellValueExplicit("AC{$rP}", (string)$v->icd10_sekunder, DataType::TYPE_STRING);
            $sheetP->setCellValue("AD{$rP}", $v->deskripsi_icd10_sekunder);
            $sheetP->setCellValue("AE{$rP}", $v->status_registrasi);
            $rP++;
        }

        // ----------------------------------------------------
        // 4. SHEET: PIVOT K (Pivot Kunjungan Total)
        // ----------------------------------------------------
        $sheetPivotK = $spreadsheet->createSheet();
        $sheetPivotK->setTitle('PIVOT K');
        $sheetPivotK->setShowGridLines(true);

        $sheetPivotK->setCellValue('A3', 'Count of NO RM');
        $sheetPivotK->setCellValue('C3', 'TYPE PASIEN');
        $sheetPivotK->setCellValue('A4', 'JENIS PEMBAYARAN');
        $sheetPivotK->setCellValue('B4', 'KELOMPOK');
        $sheetPivotK->setCellValue('C4', 'Pasien Baru');
        $sheetPivotK->setCellValue('D4', 'Pasien Lama');
        $sheetPivotK->setCellValue('E4', 'Grand Total');
        $sheetPivotK->getStyle('A4:E4')->getFont()->setBold(true);

        $pivotKData = [];
        foreach ($visits as $v) {
            $pen = $v->jenis_penjamin ?: 'LAIN-LAIN';
            $kel = $v->kelompok ?: 'UMUM / TUNAI';
            $st  = $v->status_pasien === 'Pasien Baru' ? 'baru' : 'lama';
            if (!isset($pivotKData[$pen][$kel])) {
                $pivotKData[$pen][$kel] = ['baru' => 0, 'lama' => 0];
            }
            $pivotKData[$pen][$kel][$st]++;
        }

        $rpK = 5;
        $grandBaruK = 0;
        $grandLamaK = 0;

        foreach ($pivotKData as $penName => $kelGroup) {
            $penBaru = 0;
            $penLama = 0;
            foreach ($kelGroup as $kelName => $c) {
                $sheetPivotK->setCellValue("A{$rpK}", $penName);
                $sheetPivotK->setCellValue("B{$rpK}", $kelName);
                $sheetPivotK->setCellValue("C{$rpK}", $c['baru']);
                $sheetPivotK->setCellValue("D{$rpK}", $c['lama']);
                $sheetPivotK->setCellValue("E{$rpK}", $c['baru'] + $c['lama']);
                $penBaru += $c['baru'];
                $penLama += $c['lama'];
                $rpK++;
            }
            $sheetPivotK->setCellValue("A{$rpK}", "{$penName} Total");
            $sheetPivotK->setCellValue("C{$rpK}", $penBaru);
            $sheetPivotK->setCellValue("D{$rpK}", $penLama);
            $sheetPivotK->setCellValue("E{$rpK}", $penBaru + $penLama);
            $sheetPivotK->getStyle("A{$rpK}:E{$rpK}")->getFont()->setBold(true);
            $grandBaruK += $penBaru;
            $grandLamaK += $penLama;
            $rpK++;
        }

        $sheetPivotK->setCellValue("A{$rpK}", 'Grand Total');
        $sheetPivotK->setCellValue("C{$rpK}", $grandBaruK);
        $sheetPivotK->setCellValue("D{$rpK}", $grandLamaK);
        $sheetPivotK->setCellValue("E{$rpK}", $grandBaruK + $grandLamaK);
        $sheetPivotK->getStyle("A{$rpK}:E{$rpK}")->getFont()->setBold(true);

        $sheetPivotK->getColumnDimension('A')->setWidth(26);
        $sheetPivotK->getColumnDimension('B')->setWidth(26);
        $sheetPivotK->getColumnDimension('C')->setWidth(16);
        $sheetPivotK->getColumnDimension('D')->setWidth(16);
        $sheetPivotK->getColumnDimension('E')->setWidth(16);

        // ----------------------------------------------------
        // 5. SHEET: R (Data Detail Kunjungan Raw Visits)
        // ----------------------------------------------------
        $sheetR = $spreadsheet->createSheet();
        $sheetR->setTitle('R');
        $sheetR->setShowGridLines(true);

        foreach ($headersDetail as $colIdx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetR->setCellValue("{$colLetter}1", $hText);
        }
        $sheetR->getStyle('A1:AE1')->getFont()->setBold(true);

        $rR = 2;
        $noR = 1;
        foreach ($visits as $v) {
            $sheetR->setCellValue("A{$rR}", $noR++);
            $sheetR->setCellValueExplicit("B{$rR}", (string)$v->no_rm, DataType::TYPE_STRING);
            $sheetR->setCellValue("C{$rR}", $v->nama_pasien);
            $sheetR->setCellValue("D{$rR}", $v->tgl_lahir);
            $sheetR->setCellValue("E{$rR}", $v->umur);
            $sheetR->setCellValue("F{$rR}", $v->status_pasien);
            $sheetR->setCellValueExplicit("G{$rR}", (string)$v->no_telp, DataType::TYPE_STRING);
            $sheetR->setCellValueExplicit("H{$rR}", (string)$v->no_hp, DataType::TYPE_STRING);
            $sheetR->setCellValue("I{$rR}", $v->poliklinik);
            $sheetR->setCellValue("J{$rR}", $v->dokter);
            $sheetR->setCellValue("K{$rR}", $v->tgl_berobat);
            $sheetR->setCellValue("L{$rR}", $v->jam);
            $sheetR->setCellValueExplicit("M{$rR}", (string)$v->no_sep, DataType::TYPE_STRING);
            $sheetR->setCellValueExplicit("N{$rR}", (string)$v->no_bpjs, DataType::TYPE_STRING);
            $sheetR->setCellValue("O{$rR}", $v->jenis_rawat);
            $sheetR->setCellValue("P{$rR}", $v->jenis_penjamin);
            $sheetR->setCellValue("Q{$rR}", $v->kelompok);
            $sheetR->setCellValue("R{$rR}", $v->pangkat);
            $sheetR->setCellValueExplicit("S{$rR}", (string)$v->nip_nrp_pasien, DataType::TYPE_STRING);
            $sheetR->setCellValue("T{$rR}", $v->gender);
            $sheetR->setCellValue("U{$rR}", $v->agama);
            $sheetR->setCellValue("V{$rR}", $v->pendidikan);
            $sheetR->setCellValue("W{$rR}", $v->kesatuan);
            $sheetR->setCellValue("X{$rR}", $v->instansi);
            $sheetR->setCellValue("Y{$rR}", $v->kategori);
            $sheetR->setCellValue("Z{$rR}", $v->alamat);
            $sheetR->setCellValueExplicit("AA{$rR}", (string)$v->icd10_utama, DataType::TYPE_STRING);
            $sheetR->setCellValue("AB{$rR}", $v->deskripsi_icd10_utama);
            $sheetR->setCellValueExplicit("AC{$rR}", (string)$v->icd10_sekunder, DataType::TYPE_STRING);
            $sheetR->setCellValue("AD{$rR}", $v->deskripsi_icd10_sekunder);
            $sheetR->setCellValue("AE{$rR}", $v->status_registrasi);
            $rR++;
        }

        // ----------------------------------------------------
        // 6. SHEET: Lap. kunjungan pasien (Laporan Terformat)
        // ----------------------------------------------------
        $sheetLap = $spreadsheet->createSheet();
        $sheetLap->setTitle('Lap. kunjungan pasien');
        $sheetLap->setShowGridLines(true);

        $sheetLap->setCellValue('A1', 'MARKAS BESAR TNI ANGKATAN DARAT');
        $sheetLap->setCellValue('A2', 'RSPAD GATOT SOEBROTO');
        $sheetLap->setCellValue('A3', 'Jl. Abdul Rahman Saleh No. 24, Jakarta Pusat');
        $sheetLap->setCellValue('A4', 'Telp : (021) 3441008, 3840702, Fax : (021) 3520619');

        $sheetLap->getStyle('A1:A2')->getFont()->setBold(true)->setSize(11);
        $sheetLap->getStyle('A3:A4')->getFont()->setSize(9.5);

        $sheetLap->setCellValue('A6', 'LAPORAN KUNJUNGAN PASIEN');
        $sheetLap->getStyle('A6')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E561E'));

        $sheetLap->setCellValue('A7', 'LOKASI');       $sheetLap->setCellValue('B7', ': RSPAD');
        $sheetLap->setCellValue('A8', 'POLIKLINIK');   $sheetLap->setCellValue('B8', ": {$poliTitle}");
        $sheetLap->setCellValue('A9', 'JENIS RAWAT');  $sheetLap->setCellValue('B9', ': WATLAN');
        $sheetLap->setCellValue('A10', 'STATUS REGIS'); $sheetLap->setCellValue('B10', ': OPEN');
        $sheetLap->setCellValue('A11', 'TANGGAL');    $sheetLap->setCellValue('B11', ": 01/{$mStr}/{$yStr} s/d {$lastDay}/{$mStr}/{$yStr}");

        $sheetLap->getStyle('A7:A11')->getFont()->setBold(true)->setSize(10);
        $sheetLap->getStyle('B7:B11')->getFont()->setSize(10);

        $headers1 = [
            'NO', 'NO RM', 'NAMA PASIEN', 'TANGGAL LAHIR', 'USIA', 'NO. TELP', 'NO. PONSEL',
            'POLI', 'DOKTER', 'TANGGAL', 'JAM', 'NO SEP', 'NO PESERTA', 'TYPE PASIEN',
            'JENIS RAWAT', 'JENIS PEMBAYARAN', 'KELOMPOK', 'PANGKAT', 'NRP', 'KELAMIN',
            'AGAMA', 'PENDIDIKAN', 'KESATUAN', 'ANGKATAN', 'HUBUNGAN KELUARGA', 'ALAMAT',
            'KODE', 'DIAGNOSA AWAL', 'KODE', 'DIAGNOSA AKHIR', 'STATUS'
        ];

        foreach ($headers1 as $colIdx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetLap->setCellValue("{$colLetter}13", $hText);
        }

        $sheetLap->getRowDimension(13)->setRowHeight(28);

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
        $sheetLap->getStyle('A13:AE13')->applyFromArray($headerStyle1);

        $widths1 = [
            'A' => 16,  'B' => 14,  'C' => 28,  'D' => 16,  'E' => 8,
            'F' => 18,  'G' => 18,  'H' => 24,  'I' => 28,  'J' => 14,
            'K' => 10,  'L' => 24,  'M' => 22,  'N' => 16,  'O' => 16,
            'P' => 24,  'Q' => 28,  'R' => 14,  'S' => 20,  'T' => 10,
            'U' => 12,  'V' => 14,  'W' => 24,  'X' => 18,  'Y' => 22,
            'Z' => 32,  'AA' => 10, 'AB' => 32, 'AC' => 10, 'AD' => 32, 'AE' => 14
        ];
        foreach ($widths1 as $col => $w) {
            $sheetLap->getColumnDimension($col)->setWidth($w);
        }

        $rL = 14;
        foreach ($visits as $idx => $v) {
            $sheetLap->setCellValue("A{$rL}", $idx + 1);
            $sheetLap->setCellValueExplicit("B{$rL}", (string)$v->no_rm, DataType::TYPE_STRING);
            $sheetLap->setCellValue("C{$rL}", $v->nama_pasien);
            $sheetLap->setCellValue("D{$rL}", $v->tgl_lahir);
            $sheetLap->setCellValue("E{$rL}", $v->umur);
            $sheetLap->setCellValueExplicit("F{$rL}", (string)$v->no_telp, DataType::TYPE_STRING);
            $sheetLap->setCellValueExplicit("G{$rL}", (string)$v->no_hp, DataType::TYPE_STRING);
            $sheetLap->setCellValue("H{$rL}", $v->poliklinik);
            $sheetLap->setCellValue("I{$rL}", $v->dokter);
            $sheetLap->setCellValue("J{$rL}", $v->tgl_berobat);
            $sheetLap->setCellValue("K{$rL}", $v->jam);
            $sheetLap->setCellValueExplicit("L{$rL}", (string)$v->no_sep, DataType::TYPE_STRING);
            $sheetLap->setCellValueExplicit("M{$rL}", (string)$v->no_bpjs, DataType::TYPE_STRING);
            $sheetLap->setCellValue("N{$rL}", $v->status_pasien);
            $sheetLap->setCellValue("O{$rL}", $v->jenis_rawat);
            $sheetLap->setCellValue("P{$rL}", $v->jenis_penjamin);
            $sheetLap->setCellValue("Q{$rL}", $v->kelompok);
            $sheetLap->setCellValue("R{$rL}", $v->pangkat);
            $sheetLap->setCellValueExplicit("S{$rL}", (string)$v->nip_nrp_pasien, DataType::TYPE_STRING);
            $sheetLap->setCellValue("T{$rL}", $v->gender);
            $sheetLap->setCellValue("U{$rL}", $v->agama);
            $sheetLap->setCellValue("V{$rL}", $v->pendidikan);
            $sheetLap->setCellValue("W{$rL}", $v->kesatuan);
            $sheetLap->setCellValue("X{$rL}", $v->instansi);
            $sheetLap->setCellValue("Y{$rL}", $v->kategori);
            $sheetLap->setCellValue("Z{$rL}", $v->alamat);
            $sheetLap->setCellValueExplicit("AA{$rL}", (string)$v->icd10_utama, DataType::TYPE_STRING);
            $sheetLap->setCellValue("AB{$rL}", $v->deskripsi_icd10_utama);
            $sheetLap->setCellValueExplicit("AC{$rL}", (string)$v->icd10_sekunder, DataType::TYPE_STRING);
            $sheetLap->setCellValue("AD{$rL}", $v->deskripsi_icd10_sekunder);
            $sheetLap->setCellValue("AE{$rL}", $v->status_registrasi);

            $sheetLap->getRowDimension($rL)->setRowHeight(20);
            $rL++;
        }

        $lastRowLap = $rL - 1;
        if ($lastRowLap >= 14) {
            $sheetLap->getStyle("A14:AE{$lastRowLap}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => $borderColor]
                    ]
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);

            $centerCols = ['A', 'B', 'D', 'E', 'J', 'K', 'T', 'U', 'AA', 'AC', 'AE'];
            foreach ($centerCols as $c) {
                $sheetLap->getStyle("{$c}14:{$c}{$lastRowLap}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // ----------------------------------------------------
        // 7. SHEET: rekap kunjungan (Rekapitulasi Terformat)
        // ----------------------------------------------------
        $sheetRekap = $spreadsheet->createSheet();
        $sheetRekap->setTitle('rekap kunjungan');
        $sheetRekap->setShowGridLines(true);

        $sheetRekap->setCellValue('A1', 'MARKAS BESAR TNI ANGKATAN DARAT');
        $sheetRekap->setCellValue('A2', 'RSPAD GATOT SOEBROTO');
        $sheetRekap->setCellValue('A3', 'Jl. Abdul Rahman Saleh No. 24, Jakarta Pusat');
        $sheetRekap->setCellValue('A4', 'Telp : (021) 3441008, 3840702, Fax : (021) 3520619');

        $sheetRekap->getStyle('A1:A2')->getFont()->setBold(true)->setSize(11);
        $sheetRekap->getStyle('A3:A4')->getFont()->setSize(9.5);

        $sheetRekap->setCellValue('A6', 'REKAP KUNJUNGAN');
        $sheetRekap->getStyle('A6')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E561E'));

        $sheetRekap->setCellValue('A7', 'LOKASI');       $sheetRekap->setCellValue('B7', ': RSPAD');
        $sheetRekap->setCellValue('A8', 'POLIKLINIK');   $sheetRekap->setCellValue('B8', ": {$poliTitle}");
        $sheetRekap->setCellValue('A9', 'JENIS RAWAT');  $sheetRekap->setCellValue('B9', ': WATLAN');
        $sheetRekap->setCellValue('A10', 'STATUS REGIS'); $sheetRekap->setCellValue('B10', ': OPEN');
        $sheetRekap->setCellValue('A11', 'TANGGAL');    $sheetRekap->setCellValue('B11', ": 01/{$mStr}/{$yStr} s/d {$lastDay}/{$mStr}/{$yStr}");

        $sheetRekap->getStyle('A7:A11')->getFont()->setBold(true)->setSize(10);
        $sheetRekap->getStyle('B7:B11')->getFont()->setSize(10);

        $sheetRekap->setCellValue('A13', 'NO');
        $sheetRekap->setCellValue('B13', 'GOLONGAN PERSONIL');
        $sheetRekap->setCellValue('C13', 'JUMLAH PASIEN BARU');
        $sheetRekap->setCellValue('D13', 'JUMLAH PASIEN LAMA');
        $sheetRekap->setCellValue('E13', 'JUMLAH');

        $sheetRekap->getRowDimension(13)->setRowHeight(28);
        $sheetRekap->getStyle('A13:E13')->applyFromArray($headerStyle1);

        $widthsRekap = ['A' => 16, 'B' => 32, 'C' => 22, 'D' => 22, 'E' => 18];
        foreach ($widthsRekap as $col => $w) {
            $sheetRekap->getColumnDimension($col)->setWidth($w);
        }

        $rekapGroup = [];
        foreach ($visits as $v) {
            $kel = $v->kelompok ?: 'UMUM / TUNAI';
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

        $rRekap = 14;
        $noRekap = 1;
        $totBaru = 0;
        $totLama = 0;

        foreach ($rekapGroup as $kelName => $counts) {
            $sheetRekap->setCellValue("A{$rRekap}", $noRekap++);
            $sheetRekap->setCellValue("B{$rRekap}", $kelName);
            $sheetRekap->setCellValue("C{$rRekap}", $counts['baru']);
            $sheetRekap->setCellValue("D{$rRekap}", $counts['lama']);
            $sheetRekap->setCellValue("E{$rRekap}", $counts['total']);
            $totBaru += $counts['baru'];
            $totLama += $counts['lama'];

            $sheetRekap->getRowDimension($rRekap)->setRowHeight(20);
            $rRekap++;
        }

        $lastDataRowRekap = $rRekap - 1;
        if ($lastDataRowRekap >= 14) {
            $sheetRekap->getStyle("A14:E{$lastDataRowRekap}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => $borderColor]
                    ]
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheetRekap->getStyle("A14:A{$lastDataRowRekap}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetRekap->getStyle("C14:E{$lastDataRowRekap}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheetRekap->setCellValue("A{$rRekap}", 'TOTAL');
        $sheetRekap->setCellValue("B{$rRekap}", '');
        $sheetRekap->setCellValue("C{$rRekap}", $totBaru);
        $sheetRekap->setCellValue("D{$rRekap}", $totLama);
        $sheetRekap->setCellValue("E{$rRekap}", $totBaru + $totLama);

        $sheetRekap->getRowDimension($rRekap)->setRowHeight(24);
        $sheetRekap->getStyle("A{$rRekap}:E{$rRekap}")->applyFromArray([
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
        $sheetRekap->getStyle("A{$rRekap}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheetRekap->getStyle("C{$rRekap}:E{$rRekap}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Download or Save to file
        $writer = new Xlsx($spreadsheet);
        if ($savePath) {
            $writer->save($savePath);
            return;
        }

        if (!$filename) {
            $filename = "Laporan_Kunjungan_RSPAD_{$month}_{$year}.xlsx";
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $writer->save('php://output');
        exit;
    }

    public static function exportZipForMonth($month, $year)
    {
        $query = RawVisit::query();
        if ($month) {
            $query->whereMonth('tgl_berobat', $month);
        }
        if ($year) {
            $query->whereYear('tgl_berobat', $year);
        }

        $polis = $query->distinct('poliklinik')->pluck('poliklinik')->filter()->values();

        $tempDir = storage_path("app/temp_zip_" . time());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $zipFile = storage_path("app/Laporan_Per_Poli_RSPAD_{$month}_{$year}.zip");

        $filesCreated = [];
        foreach ($polis as $poli) {
            $safePoli = preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli);
            $fileName = "{$safePoli}.xlsx";
            $filePath = "{$tempDir}/{$fileName}";
            self::exportFullOutput($month, $year, $fileName, $poli, $filePath);
            $filesCreated[$fileName] = $filePath;
        }

        // Also add SEMUA POLIKLINIK file
        $allFileName = "SEMUA_POLIKLINIK.xlsx";
        $allFilePath = "{$tempDir}/{$allFileName}";
        self::exportFullOutput($month, $year, $allFileName, 'SEMUA', $allFilePath);
        $filesCreated[$allFileName] = $allFilePath;

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($filesCreated as $name => $path) {
                if (file_exists($path)) {
                    $zip->addFile($path, $name);
                }
            }
            $zip->close();
        }

        // Clean temp excel files
        foreach ($filesCreated as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        @rmdir($tempDir);

        $zipName = "Laporan_Per_Poli_RSPAD_{$month}_{$year}.zip";
        header('Content-Type: application/zip');
        header("Content-Disposition: attachment; filename=\"{$zipName}\"");
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        @unlink($zipFile);
        exit;
    }
}
