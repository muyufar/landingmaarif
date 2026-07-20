<?php

declare(strict_types=1);

function distribusiSuratJalanTemplatePath(): string
{
    return APP_ROOT . '/data/templates/surat_jalan_lkpd_mi.xlsx';
}

function distribusiSuratJalanRowKelasMap(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [];
    for ($row = 7; $row <= 12; $row++) {
        $map[$row] = 1;
    }
    for ($row = 13; $row <= 18; $row++) {
        $map[$row] = 2;
    }
    for ($row = 19; $row <= 25; $row++) {
        $map[$row] = 3;
    }
    for ($row = 26; $row <= 32; $row++) {
        $map[$row] = 4;
    }
    for ($row = 33; $row <= 39; $row++) {
        $map[$row] = 5;
    }
    for ($row = 40; $row <= 46; $row++) {
        $map[$row] = 6;
    }

    return $map;
}

function formatTanggalIndonesia(?DateTimeInterface $date = null): string
{
    $date ??= new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return $date->format('j') . ' ' . ($bulan[(int) $date->format('n')] ?? '') . ' ' . $date->format('Y');
}

function distribusiSuratJalanGuruInsertAfterRow(): array
{
    return [1 => 12, 2 => 18, 3 => 25, 4 => 32, 5 => 39, 6 => 46];
}

function xlsxAppendSharedString(string $xml, string $value): array
{
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($xml);
    $items = $dom->getElementsByTagName('si');
    $index = $items->length;

    $si = $dom->createElement('si');
    $t = $dom->createElement('t');
    $t->appendChild($dom->createTextNode($value));
    $si->appendChild($t);
    $dom->documentElement->appendChild($si);

    $countNode = $dom->documentElement->getAttribute('count');
    $uniqueNode = $dom->documentElement->getAttribute('uniqueCount');
    if ($countNode !== '') {
        $dom->documentElement->setAttribute('count', (string) ((int) $countNode + 1));
    }
    if ($uniqueNode !== '') {
        $dom->documentElement->setAttribute('uniqueCount', (string) ((int) $uniqueNode + 1));
    }

    return [$dom->saveXML($dom->documentElement), $index];
}

function xlsxShiftMergeRef(string $ref, int $fromRow, int $delta): string
{
    if (!preg_match('/^([A-Z]+)(\d+)(?::([A-Z]+)(\d+))?$/', $ref, $m)) {
        return $ref;
    }

    if (!isset($m[3])) {
        $rowNum = (int) $m[2];
        if ($rowNum >= $fromRow) {
            return $m[1] . ($rowNum + $delta);
        }

        return $ref;
    }

    $startRow = (int) $m[2];
    $endRow = (int) $m[4];
    if ($startRow >= $fromRow) {
        $startRow += $delta;
    }
    if ($endRow >= $fromRow) {
        $endRow += $delta;
    }

    return $m[1] . $startRow . ':' . $m[3] . $endRow;
}

function xlsxShiftSheetRowsFrom(string $sheetXml, int $fromRow, int $delta): string
{
    if ($delta === 0) {
        return $sheetXml;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($sheetXml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    foreach ($xpath->query('//m:row') as $row) {
        $r = (int) $row->getAttribute('r');
        if ($r >= $fromRow) {
            $row->setAttribute('r', (string) ($r + $delta));
        }
    }

    foreach ($xpath->query('//m:c') as $cell) {
        $ref = $cell->getAttribute('r');
        if (preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
            $rowNum = (int) $m[2];
            if ($rowNum >= $fromRow) {
                $cell->setAttribute('r', $m[1] . ($rowNum + $delta));
            }
        }
    }

    foreach ($xpath->query('//m:mergeCell') as $mergeCell) {
        $ref = $mergeCell->getAttribute('ref');
        $mergeCell->setAttribute('ref', xlsxShiftMergeRef($ref, $fromRow, $delta));
    }

    foreach ($xpath->query('//m:f') as $formula) {
        $formula->nodeValue = preg_replace_callback(
            '/([A-Z]+)(\d+)/',
            static function (array $match) use ($fromRow, $delta): string {
                $rowNum = (int) $match[2];
                if ($rowNum >= $fromRow) {
                    return $match[1] . ($rowNum + $delta);
                }

                return $match[0];
            },
            $formula->nodeValue
        ) ?? $formula->nodeValue;
    }

    return $dom->saveXML($dom->documentElement);
}

function xlsxSetCellValueDom(DOMDocument $dom, DOMElement $cell, string $value, bool $shared = false): void
{
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $remove = [];
    foreach ($cell->childNodes as $child) {
        if ($child instanceof DOMElement && in_array($child->localName, ['f', 'v'], true)) {
            $remove[] = $child;
        }
    }
    foreach ($remove as $node) {
        $cell->removeChild($node);
    }

    if ($shared) {
        $cell->setAttribute('t', 's');
    } elseif ($cell->hasAttribute('t')) {
        $cell->removeAttribute('t');
    }

    $v = $dom->createElementNS($ns, 'v');
    $v->appendChild($dom->createTextNode($value));
    $cell->appendChild($v);
}

function xlsxClearCellValueDom(DOMElement $cell): void
{
    $remove = [];
    foreach ($cell->childNodes as $child) {
        if ($child instanceof DOMElement && in_array($child->localName, ['f', 'v'], true)) {
            $remove[] = $child;
        }
    }
    foreach ($remove as $node) {
        $cell->removeChild($node);
    }
    if ($cell->hasAttribute('t')) {
        $cell->removeAttribute('t');
    }
}

function xlsxAddMergeCells(string $sheetXml, array $refs): string
{
    if ($refs === []) {
        return $sheetXml;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($sheetXml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    $mergeCells = $xpath->query('//m:mergeCells')->item(0);
    if (!$mergeCells instanceof DOMElement) {
        $worksheet = $xpath->query('//m:worksheet')->item(0);
        if (!$worksheet instanceof DOMElement) {
            return $sheetXml;
        }
        $mergeCells = $dom->createElementNS($ns, 'mergeCells');
        $worksheet->appendChild($mergeCells);
    }

    foreach ($refs as $ref) {
        $mergeCell = $dom->createElementNS($ns, 'mergeCell');
        $mergeCell->setAttribute('ref', $ref);
        $mergeCells->appendChild($mergeCell);
    }

    $mergeCells->setAttribute('count', (string) $mergeCells->getElementsByTagName('mergeCell')->length);

    return $dom->saveXML($dom->documentElement);
}

function xlsxCloneGuruRow(string $sheetXml, int $rowNum, int $kelas, int $qty, int $labelIdx): string
{
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($sheetXml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $templateRow = $xpath->query('//m:row[@r="12"]')->item(0);
    if (!$templateRow instanceof DOMElement) {
        return $sheetXml;
    }

    $newRow = $templateRow->cloneNode(true);
    if (!$newRow instanceof DOMElement) {
        return $sheetXml;
    }

    $newRow->setAttribute('r', (string) $rowNum);

    foreach ($newRow->getElementsByTagName('c') as $cell) {
        if (!$cell instanceof DOMElement) {
            continue;
        }

        $ref = $cell->getAttribute('r');
        if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
            continue;
        }

        $col = $m[1];
        $cell->setAttribute('r', $col . $rowNum);

        switch ($col) {
            case 'C':
                xlsxSetCellValueDom($dom, $cell, (string) $labelIdx, true);
                break;
            case 'E':
                xlsxSetCellValueDom($dom, $cell, (string) $kelas);
                break;
            case 'F':
                xlsxSetCellValueDom($dom, $cell, (string) $qty);
                break;
            case 'B':
            case 'G':
            case 'H':
                xlsxClearCellValueDom($cell);
                break;
            case 'D':
                xlsxClearCellValueDom($cell);
                break;
        }
    }

    $anchor = $xpath->query('//m:row[@r="' . ($rowNum + 1) . '"]')->item(0);
    $sheetData = $xpath->query('//m:sheetData')->item(0);
    if (!$sheetData instanceof DOMElement) {
        return $sheetXml;
    }

    if ($anchor instanceof DOMElement) {
        $sheetData->insertBefore($newRow, $anchor);
    } else {
        $sheetData->appendChild($newRow);
    }

    return $dom->saveXML($dom->documentElement);
}

function xlsxRenumberColumnB(string $sheetXml, int $fromRow, int $toRow): string
{
    $no = 1;
    for ($row = $fromRow; $row <= $toRow; $row++) {
        $sheetXml = xlsxSetNumericCell($sheetXml, 'B' . $row, $no);
        $no++;
    }

    return $sheetXml;
}

function xlsxInsertGuruRowsInSuratJalan(string $sheetXml, string $sharedXml, array $satuan): array
{
    [$sharedXml, $labelIdx] = xlsxAppendSharedString($sharedXml, 'BUKU GURU');
    $insertAfter = distribusiSuratJalanGuruInsertAfterRow();
    $inserted = 0;

    for ($kelas = 6; $kelas >= 1; $kelas--) {
        $qty = (int) ($satuan['kebutuhan_guru_kelas_' . $kelas] ?? 0);
        if ($qty <= 0) {
            continue;
        }

        $after = $insertAfter[$kelas];
        $newRow = $after + 1;
        $sheetXml = xlsxShiftSheetRowsFrom($sheetXml, $newRow, 1);
        $sheetXml = xlsxCloneGuruRow($sheetXml, $newRow, $kelas, $qty, $labelIdx);
        $sheetXml = xlsxAddMergeCells($sheetXml, [
            'C' . $newRow . ':D' . $newRow,
            'G' . $newRow . ':H' . $newRow,
        ]);
        $inserted++;
    }

    if ($inserted > 0) {
        $lastDataRow = 46 + $inserted;
        $sheetXml = xlsxRenumberColumnB($sheetXml, 7, $lastDataRow);
        $sheetXml = preg_replace(
            '/<f>SUM\(F7:F\d+\)<\/f>/',
            '<f>SUM(F7:F' . $lastDataRow . ')</f>',
            $sheetXml
        ) ?? $sheetXml;
    }

    return [$sheetXml, $sharedXml];
}

function xlsxReplaceSharedString(string $xml, int $index, string $value): string
{
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($xml);
    $items = $dom->getElementsByTagName('si');
    if ($items->length <= $index) {
        throw new RuntimeException('Shared string index tidak ditemukan.');
    }

    $si = $items->item($index);
    while ($si->firstChild !== null) {
        $si->removeChild($si->firstChild);
    }

    $t = $dom->createElement('t');
    $t->appendChild($dom->createTextNode($value));
    $si->appendChild($t);

    return $dom->saveXML($dom->documentElement);
}

function xlsxSetNumericCell(string $sheetXml, string $ref, int $value): string
{
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($sheetXml);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $nodes = $xpath->query('//m:c[@r="' . $ref . '"]');
    if ($nodes === false || $nodes->length === 0) {
        return $sheetXml;
    }

    /** @var DOMElement $cell */
    $cell = $nodes->item(0);
    if ($cell->hasAttribute('t')) {
        $cell->removeAttribute('t');
    }

    $remove = [];
    foreach ($cell->childNodes as $child) {
        if ($child instanceof DOMElement && in_array($child->localName, ['f', 'v'], true)) {
            $remove[] = $child;
        }
    }
    foreach ($remove as $node) {
        $cell->removeChild($node);
    }

    $v = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v');
    $v->appendChild($dom->createTextNode((string) $value));
    $cell->appendChild($v);

    return $dom->saveXML($dom->documentElement);
}


function buildDistribusiSuratJalanXlsx(array $satuan, ?array $petugas = null): string
{
    $template = distribusiSuratJalanTemplatePath();
    if (!is_file($template)) {
        throw new RuntimeException('Template surat jalan tidak ditemukan.');
    }

    $tmpIn = tempnam(sys_get_temp_dir(), 'sj_in_');
    if ($tmpIn === false) {
        throw new RuntimeException('Gagal menyiapkan file sementara.');
    }

    copy($template, $tmpIn);

    $zip = new ZipArchive();
    if ($zip->open($tmpIn) !== true) {
        throw new RuntimeException('Gagal membuka template surat jalan.');
    }

    $sharedXml = (string) $zip->getFromName('xl/sharedStrings.xml');
    $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sharedXml === '' || $sheetXml === '') {
        $zip->close();
        throw new RuntimeException('Struktur template surat jalan tidak valid.');
    }

    $nama = trim((string) ($satuan['nama_lembaga'] ?? ''));
    $petugasNama = trim((string) ($petugas['nama'] ?? 'Petugas Distribusi'));
    $tanggal = formatTanggalIndonesia();

    $sharedXml = xlsxReplaceSharedString($sharedXml, 6, 'Dikirim Tanggal : ' . $tanggal);
    $sharedXml = xlsxReplaceSharedString($sharedXml, 31, 'Nama Madrasah  : ' . $nama);
    $sharedXml = xlsxReplaceSharedString($sharedXml, 9, $petugasNama);

    foreach (distribusiSuratJalanRowKelasMap() as $row => $kelas) {
        $qty = (int) ($satuan['kebutuhan_kelas_' . $kelas] ?? 0);
        $sheetXml = xlsxSetNumericCell($sheetXml, 'F' . $row, $qty);
    }

    [$sheetXml, $sharedXml] = xlsxInsertGuruRowsInSuratJalan($sheetXml, $sharedXml, $satuan);

    $zip->addFromString('xl/sharedStrings.xml', $sharedXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->deleteName('xl/calcChain.xml');
    $zip->close();

    $binary = (string) file_get_contents($tmpIn);
    @unlink($tmpIn);

    if ($binary === '') {
        throw new RuntimeException('File surat jalan kosong.');
    }

    return $binary;
}

function streamDistribusiSuratJalanGenerated(array $satuan, ?array $petugas = null): void
{
    $binary = buildDistribusiSuratJalanXlsx($satuan, $petugas);
    $npsn = preg_replace('/\D/', '', (string) ($satuan['npsn'] ?? '')) ?: 'satuan';
    $filename = 'Surat_Jalan_LKPD_' . $npsn . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($binary));
    header('Cache-Control: no-store');
    echo $binary;
    exit;
}

function parseSuratJalanOcrText(string $text): array
{
    $lines = preg_split('/\R/u', strtoupper($text)) ?: [];
    $kelas = array_fill(1, 6, 0);
    $guru = array_fill(1, 6, 0);
    $found = array_fill(1, 6, false);
    $foundGuru = array_fill(1, 6, false);

    $subjectKeys = [
        'BAHASA INDONESIA', 'PENDIDIKAN PANCASILA', 'PENDIDAN PANCASILA', 'PENDIDIKAN',
        'MATEMATIKA', 'BAHASA JAWA', 'BAHASA INGGRIS', 'AGAMA', 'IPAS',
    ];

    $extractQty = static function (string $line): int {
        if (!preg_match_all('/\b(\d{1,4})\b/u', $line, $nums) || empty($nums[1])) {
            return 0;
        }
        $qty = (int) end($nums[1]);

        return ($qty > 0 && $qty <= 9999) ? $qty : 0;
    };

    foreach ($lines as $line) {
        $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
        if ($line === '') {
            continue;
        }

        if (str_contains($line, 'BUKU GURU')) {
            for ($k = 1; $k <= 6; $k++) {
                if (!preg_match('/\b' . $k . '\b/u', $line)) {
                    continue;
                }
                $qty = $extractQty($line);
                if ($qty > 0) {
                    $guru[$k] = $qty;
                    $foundGuru[$k] = true;
                }
            }
            continue;
        }

        $hasSubject = false;
        foreach ($subjectKeys as $key) {
            if (str_contains($line, $key)) {
                $hasSubject = true;
                break;
            }
        }

        if (!$hasSubject) {
            continue;
        }

        for ($k = 1; $k <= 6; $k++) {
            if (!preg_match('/\b' . $k . '\b/u', $line)) {
                continue;
            }
            $qty = $extractQty($line);
            if ($qty > 0) {
                $kelas[$k] = $qty;
                $found[$k] = true;
            }
        }
    }

    for ($k = 1; $k <= 6; $k++) {
        if ($found[$k]) {
            continue;
        }
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
            if (!preg_match('/\bKELAS\b/u', $line) && !preg_match('/\bKLS\b/u', $line)) {
                continue;
            }
            if (!preg_match('/\b' . $k . '\b/u', $line)) {
                continue;
            }
            if (preg_match_all('/\b(\d{1,4})\b/u', $line, $nums) && !empty($nums[1])) {
                $values = array_map('intval', $nums[1]);
                $qty = (int) end($values);
                if ($qty > 0) {
                    $kelas[$k] = $qty;
                    $found[$k] = true;
                    break;
                }
            }
        }
    }

    $detected = count(array_filter($found));
    $detectedGuru = count(array_filter($foundGuru));

    return [
        'kelas' => $kelas,
        'guru' => $guru,
        'detected' => $detected,
        'detected_guru' => $detectedGuru,
        'ok' => $detected > 0 || $detectedGuru > 0,
    ];
}
