<?php

declare(strict_types=1);

/**
 * Generate RAKERDINMA certificate PNG from template + participant data.
 */

function sertifikatTemplatePath(): string
{
    return APP_ROOT . '/image/sertifmaarif.png';
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
    return is_file(sertifikatTemplatePath())
        && is_file(sertifikatFontBold())
        && is_file(sertifikatFontRegular());
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

function sertifikatDrawCenteredTextWithUnderline(
    \GdImage $image,
    string $font,
    int $size,
    string $text,
    int $centerX,
    int $baselineY,
    int $color,
    int $paddingX = 40,
    int $lineGap = 18
): void {
    sertifikatDrawCenteredText($image, $font, $size, $text, $centerX, $baselineY, $color);

    $metrics = sertifikatMeasureText($font, $size, $text);
    $textX = (int) round($centerX - ($metrics['width'] / 2));
    $lineY = $baselineY + $lineGap;
    imageline(
        $image,
        $textX - $paddingX,
        $lineY,
        $textX + $metrics['width'] + $paddingX,
        $lineY,
        $color
    );
}

function sertifikatPatchBackground(\GdImage $image, int $destX, int $destY, int $width, int $height, int $sourceY): void
{
    imagecopy($image, $image, $destX, $destY, $destX, $sourceY, $width, $height);
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

    $patchX = (int) round(520 * $scaleX);
    $patchY = (int) round(1450 * $scaleY);
    $patchW = (int) round(3710 * $scaleX);
    $patchH = (int) round(520 * $scaleY);
    $patchSourceY = (int) round(1320 * $scaleY);
    sertifikatPatchBackground($image, $patchX, $patchY, $patchW, $patchH, $patchSourceY);

    $green = imagecolorallocate($image, 0, 77, 0);
    $black = imagecolorallocate($image, 0, 0, 0);

    $fontBold = sertifikatFontBold();
    $maxNameWidth = (int) round(3400 * $scaleX);
    $nameSize = sertifikatFitFontSize($fontBold, $nama, (int) round(130 * $scaleY), (int) round(72 * $scaleY), $maxNameWidth);
    $nameBaseline = (int) round(1610 * $scaleY);
    sertifikatDrawCenteredText($image, $fontBold, $nameSize, $nama, $centerX, $nameBaseline, $green);

    $maxLembagaWidth = (int) round(3000 * $scaleX);
    $lembagaSize = sertifikatFitFontSize($fontBold, $lembaga, (int) round(82 * $scaleY), (int) round(48 * $scaleY), $maxLembagaWidth);
    $lembagaBaseline = (int) round(1835 * $scaleY);
    sertifikatDrawCenteredTextWithUnderline(
        $image,
        $fontBold,
        $lembagaSize,
        $lembaga,
        $centerX,
        $lembagaBaseline,
        $black,
        (int) round(24 * $scaleX),
        (int) round(14 * $scaleY)
    );

    return $image;
}

function outputSertifikatPng(array $peserta, bool $download = true): void
{
    $image = generateSertifikatImage($peserta);
    $filename = 'Sertifikat_RAKERDINMA_' . sertifikatSanitizeFilename($peserta['nama'] ?? 'peserta') . '.png';

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
