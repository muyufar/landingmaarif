<?php

declare(strict_types=1);

/**
 * Generate RAKERDINMA certificate PNG from blank template + participant data.
 */

function sertifikatTemplatePath(): string
{
    $candidates = [
        APP_ROOT . '/image/sertifikatnomorfix.png',
        APP_ROOT . '/image/sertifkosonganfix.png',
        APP_ROOT . '/image/sertifkosongan.png',
        APP_ROOT . '/image/sertifmaarif.png',
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return $candidates[0];
}

function sertifikatUsesNomorFixTemplate(): bool
{
    return is_file(APP_ROOT . '/image/sertifikatnomorfix.png')
        && sertifikatTemplatePath() === APP_ROOT . '/image/sertifikatnomorfix.png';
}

function sertifikatFontPoppins(): string
{
    return APP_ROOT . '/image/fonts/Poppins-Regular.ttf';
}

function sertifikatFontBold(): string
{
    return APP_ROOT . '/image/fonts/timesbd.ttf';
}

function sertifikatFontRegular(): string
{
    return APP_ROOT . '/image/fonts/times.ttf';
}

function sertifikatFontsAvailable(): bool
{
    if (!is_file(sertifikatTemplatePath())) {
        return false;
    }

    if (sertifikatUsesNomorFixTemplate()) {
        return is_file(sertifikatFontPoppins()) && is_file(sertifikatFontBold());
    }

    return is_file(sertifikatFontBold()) && is_file(sertifikatFontRegular());
}

function sertifikatGdAvailable(): bool
{
    return extension_loaded('gd') && function_exists('imagecreatefrompng');
}

function sertifikatCanGenerate(): bool
{
    return sertifikatGdAvailable() && sertifikatFontsAvailable();
}

function sertifikatSanitizeFilename(string $name): string
{
    $name = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $name) ?? 'peserta';
    $name = preg_replace('/\s+/', '_', trim($name)) ?? 'peserta';

    return $name !== '' ? $name : 'peserta';
}

function sertifikatScaleFontPt(int $pt, float $scaleY): int
{
    $refHeight = SERTIFIKAT_DESIGN_HEIGHT > 0 ? SERTIFIKAT_DESIGN_HEIGHT : 794;

    return max(1, (int) round($pt * $scaleY * (3359 / $refHeight)));
}

function sertifikatMeasureText(string $font, int $size, string $text): array
{
    $bbox = imagettfbbox($size, 0, $font, $text) ?: [0, 0, 0, 0, 0, 0, 0, 0];

    return [
        'width' => abs($bbox[2] - $bbox[0]),
        'height' => abs($bbox[7] - $bbox[1]),
    ];
}

function sertifikatFitFontSize(string $font, string $text, int $maxSize, int $minSize, int $maxWidth): int
{
    for ($size = $maxSize; $size >= $minSize; $size -= 2) {
        if (sertifikatMeasureText($font, $size, $text)['width'] <= $maxWidth) {
            return $size;
        }
    }

    return $minSize;
}

function sertifikatDrawCenteredText(
    \GdImage $image,
    string $font,
    int $size,
    string $text,
    int $centerX,
    int $baselineY,
    int $color
): void {
    $metrics = sertifikatMeasureText($font, $size, $text);
    $x = (int) round($centerX - ($metrics['width'] / 2));
    imagettftext($image, $size, 0, $x, $baselineY, $color, $font, $text);
}

function sertifikatNomorUrut(array $peserta): int
{
    $pesertaId = (int) ($peserta['id'] ?? 0);

    return getNomorSertifikatPeserta($pesertaId);
}

function sertifikatNomorTeks(array $peserta): string
{
    return formatNomorSertifikat(sertifikatNomorUrut($peserta));
}

function generateSertifikatImage(array $peserta): \GdImage
{
    if (!sertifikatCanGenerate()) {
        throw new RuntimeException('Modul sertifikat belum siap (GD/font/template).');
    }

    $templatePath = sertifikatTemplatePath();
    $image = imagecreatefrompng($templatePath);
    if ($image === false) {
        throw new RuntimeException('Gagal memuat template sertifikat.');
    }

    imagesavealpha($image, true);
    imagealphablending($image, true);

    $width = imagesx($image);
    $height = imagesy($image);
    $centerX = (int) round($width / 2);

    $nama = strtoupper(trim($peserta['nama'] ?? ''));
    $lembaga = strtoupper(trim($peserta['asal_lembaga'] ?? ''));

    if ($nama === '' || $lembaga === '') {
        imagedestroy($image);
        throw new RuntimeException('Data peserta tidak lengkap untuk sertifikat.');
    }

    // Koordinat relatif terhadap template 4750×3359 px
    $scaleX = $width / 4750;
    $scaleY = $height / 3359;

    $green = imagecolorallocate($image, 0, 77, 0);
    $black = imagecolorallocate($image, 0, 0, 0);
    $fontBold = sertifikatFontBold();

    if (sertifikatUsesNomorFixTemplate()) {
        $fontPoppins = sertifikatFontPoppins();
        $nomorText = sertifikatNomorTeks($peserta);
        $nomorSize = sertifikatScaleFontPt(SERTIFIKAT_NOMOR_FONT_PT, $scaleY);

        sertifikatDrawCenteredText(
            $image,
            $fontPoppins,
            $nomorSize,
            $nomorText,
            $centerX,
            (int) round(985 * $scaleY),
            $black
        );

        $maxNameWidth = (int) round(3600 * $scaleX);
        $nameSize = sertifikatFitFontSize($fontBold, $nama, (int) round(125 * $scaleY), (int) round(68 * $scaleY), $maxNameWidth);
        sertifikatDrawCenteredText($image, $fontBold, $nameSize, $nama, $centerX, (int) round(1355 * $scaleY), $green);

        $maxLembagaWidth = (int) round(3400 * $scaleX);
        $lembagaSize = sertifikatFitFontSize($fontBold, $lembaga, (int) round(88 * $scaleY), (int) round(50 * $scaleY), $maxLembagaWidth);
        sertifikatDrawCenteredText($image, $fontBold, $lembagaSize, $lembaga, $centerX, (int) round(1455 * $scaleY), $black);
    } else {
        $fontRegular = sertifikatFontRegular();
        $nomorText = sertifikatNomorTeks($peserta);
        $nomorSize = sertifikatScaleFontPt(SERTIFIKAT_NOMOR_FONT_PT, $scaleY);

        sertifikatDrawCenteredText(
            $image,
            is_file(sertifikatFontPoppins()) ? sertifikatFontPoppins() : $fontRegular,
            $nomorSize,
            $nomorText,
            $centerX,
            (int) round(1125 * $scaleY),
            $black
        );

        $maxNameWidth = (int) round(3600 * $scaleX);
        $nameSize = sertifikatFitFontSize($fontBold, $nama, (int) round(110 * $scaleY), (int) round(58 * $scaleY), $maxNameWidth);
        sertifikatDrawCenteredText($image, $fontBold, $nameSize, $nama, $centerX, (int) round(1460 * $scaleY), $green);

        $maxLembagaWidth = (int) round(3400 * $scaleX);
        $lembagaSize = sertifikatFitFontSize($fontBold, $lembaga, (int) round(72 * $scaleY), (int) round(42 * $scaleY), $maxLembagaWidth);
        sertifikatDrawCenteredText($image, $fontBold, $lembagaSize, $lembaga, $centerX, (int) round(1590 * $scaleY), $black);
    }

    return $image;
}

function outputSertifikatPng(array $peserta, bool $download = true): void
{
    $image = generateSertifikatImage($peserta);
    $nomor = sertifikatNomorUrut($peserta);
    $filename = 'Sertifikat_RAKERDINMA_' . $nomor . '_' . sertifikatSanitizeFilename($peserta['nama'] ?? 'peserta') . '.png';

    header('Content-Type: image/png');
    if ($download) {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    } else {
        header('Content-Disposition: inline; filename="' . $filename . '"');
    }
    header('Cache-Control: no-store, no-cache, must-revalidate');

    imagepng($image, null, 6);
    imagedestroy($image);
    exit;
}
