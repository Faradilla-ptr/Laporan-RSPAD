<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use PhpOffice\PhpSpreadsheet\IOFactory;

$files = array_merge(
    glob(base_path('rspad-file/JUNI/*.xls')) ?: [],
    glob(base_path('rspad-file/B. URO/*.xls')) ?: [],
    glob(base_path('rspad-file/AGUSTUS/*.xls')) ?: []
);

$filePath = $files[0];
echo "Inspecting file: {$filePath}\n";

$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('R');

if ($sheet) {
    echo "=== Sheet R Row 1 & 2 ===\n";
    for ($r = 1; $r <= 3; $r++) {
        $rowVal = [];
        for ($c = 'A'; $c != 'AE'; $c++) {
            $val = $sheet->getCell($c.$r)->getValue();
            $rowVal[$c] = (string) $val;
        }
        echo "Row {$r}: ".json_encode($rowVal, JSON_UNESCAPED_UNICODE)."\n";
    }
}
