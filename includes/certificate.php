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

function sertifikatFontPlayfair(): string
{
    return APP_ROOT . '/image/fonts/PlayfairDisplay-Bold.ttf';
}

function sertifikatFontTermes(): string
{
    $otf = APP_ROOT . '/image/fonts/texgyretermes-bold.otf';
    if (is_file($otf)) {
        return $otf;
    }

    return APP_ROOT . '/image/fonts/texgyretermes-bold.ttf';
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
        return is_file(sertifikatFontPoppins())
            && is_file(sertifikatFontPlayfair())
            && is_file(sertifikatFontTermes());
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

function sertifikatScaleFontPt(float $pt, float $scaleY): int
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
    for ($size = $maxSize; $size >= $minSize; $size--) {
        if (sertifikatMeasureText($font, $size, $text)['width'] <= $maxWidth) {
            return $size;
        }
    }

    return $minSize;
}

function sertifikatFitFontSizePt(
    string $font,
    string $text,
    float $maxPt,
    float $minPt,
    float $scaleY,
    int $maxWidth
): int {
    return sertifikatFitFontSize(
        $font,
        $text,
        sertifikatScaleFontPt($maxPt, $scaleY),
        sertifikatScaleFontPt($minPt, $scaleY),
        $maxWidth
    );
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
    return 'Nomor : ' . formatNomorSertifikat(sertifikatNomorUrut($peserta));
}

function sertifikatDrawNomorFixLayout(
    \GdImage $image,
    array $peserta,
    int $centerX,
    float $scaleX,
    float $scaleY,
    int $green,
    int $black
): void {
    $fontPoppins = sertifikatFontPoppins();
    $fontPlayfair = sertifikatFontPlayfair();
    $fontTermes = sertifikatFontTermes();

    $nama = trim($peserta['nama'] ?? '');
    $lembaga = trim($peserta['asal_lembaga'] ?? '');

    $nomorSize = sertifikatScaleFontPt(SERTIFIKAT_NOMOR_FONT_PT, $scaleY);
    sertifikatDrawCenteredText(
        $image,
        $fontPoppins,
        $nomorSize,
        sertifikatNomorTeks($peserta),
        $centerX,
        (int) round(SERTIFIKAT_Y_NOMOR * $scaleY),
        $black
    );

    $maxNameWidth = (int) round(SERTIFIKAT_MAX_TEXT_WIDTH * $scaleX);
    $nameSize = sertifikatFitFontSizePt(
        $fontPlayfair,
        $nama,
        SERTIFIKAT_NAMA_FONT_PT,
        SERTIFIKAT_NAMA_FONT_MIN_PT,
        $scaleY,
        $maxNameWidth
    );
    sertifikatDrawCenteredText(
        $image,
        $fontPlayfair,
        $nameSize,
        $nama,
        $centerX,
        (int) round(SERTIFIKAT_Y_NAMA * $scaleY),
        $green
    );

    $maxLembagaWidth = (int) round(SERTIFIKAT_MAX_TEXT_WIDTH * $scaleX);
    $lembagaSize = sertifikatFitFontSizePt(
        $fontTermes,
        $lembaga,
        SERTIFIKAT_LEMBAGA_FONT_PT,
        SERTIFIKAT_LEMBAGA_FONT_MIN_PT,
        $scaleY,
        $maxLembagaWidth
    );
    sertifikatDrawCenteredText(
        $image,
        $fontTermes,
        $lembagaSize,
        $lembaga,
        $centerX,
        (int) round(SERTIFIKAT_Y_LEMBAGA * $scaleY),
        $black
    );
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

    $nama = trim($peserta['nama'] ?? '');
    $lembaga = trim($peserta['asal_lembaga'] ?? '');

    if ($nama === '' || $lembaga === '') {
        imagedestroy($image);
        throw new RuntimeException('Data peserta tidak lengkap untuk sertifikat.');
    }

    // Koordinat relatif terhadap template 4750×3359 px
    $scaleX = $width / 4750;
    $scaleY = $height / 3359;

    $green = imagecolorallocate($image, 0, 77, 0);
    $black = imagecolorallocate($image, 0, 0, 0);

    sertifikatDrawNomorFixLayout($image, $peserta, $centerX, $scaleX, $scaleY, $green, $black);

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
