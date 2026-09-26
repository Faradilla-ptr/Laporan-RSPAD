<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use PhpOffice\PhpSpreadsheet\IOFactory;

$folders = [
    base_path('rspad-file/JUNI'),
    base_path('rspad-file/B. URO'),
    base_path('rspad-file/AGUSTUS'),
];

$allHeaders = [];

foreach ($folders as $folder) {
    if (! is_dir($folder)) {
        continue;
    }
    $files = glob("{$folder}/*.xls");
    foreach ($files as $f) {
        try {
            $spreadsheet = IOFactory::load($f);
            $sheet = null;
            if ($spreadsheet->sheetNameExists('R')) {
                $sheet = $spreadsheet->getSheetByName('R');
            } elseif ($spreadsheet->sheetNameExists('Lap. kunjungan pasien')) {
                $sheet = $spreadsheet->getSheetByName('Lap. kunjungan pasien');
            } else {
                $sheet = $spreadsheet->getSheet(0);
            }

            // Find header row (1 to 20)
            $headerRow = null;
            $highestRow = min($sheet->getHighestRow(), 25);
            for ($r = 1; $r <= $highestRow; $r++) {
                $cB = strtoupper(trim((string) $sheet->getCell("B{$r}")->getValue()));
                $cA = strtoupper(trim((string) $sheet->getCell("A{$r}")->getValue()));
                if ($cB === 'NO RM' || str_contains($cB, 'RM') || $cA === 'NO RM') {
                    $headerRow = $r;
                    break;
                }
            }

            if ($headerRow) {
                $colMap = [];
                foreach (range('A', 'Z') as $col) {
                    $val = strtoupper(trim((string) $sheet->getCell("{$col}{$headerRow}")->getValue()));
                    if ($val) {
                        $colMap[$col] = $val;
                    }
                }
                $allHeaders[basename($f).' ('.$sheet->getTitle().')'] = $colMap;
            }
        } catch (Exception $e) {
            echo 'Error loading '.basename($f).': '.$e->getMessage()."\n";
        }
    }
}

echo 'Found header maps for '.count($allHeaders)." files.\n\n";
// Print sample 3 maps
$sample = array_slice($allHeaders, 0, 3, true);
foreach ($sample as $name => $map) {
    echo "=== {$name} ===\n";
    echo json_encode($map, JSON_UNESCAPED_UNICODE)."\n\n";
}
