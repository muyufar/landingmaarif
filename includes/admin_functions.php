<?php

declare(strict_types=1);

require_once __DIR__ . '/berita_functions.php';

function getAdminModules(): array
{
    return [
        [
            'key' => 'hub',
            'label' => 'Portal Admin',
            'desc' => 'Ringkasan semua layanan',
            'url' => url('admin/?page=dashboard'),
            'icon' => '🏠',
            'internal' => true,
        ],
        [
            'key' => 'berita',
            'label' => 'Kelola Berita',
            'desc' => 'Upload, edit, dan publikasikan berita',
            'url' => url('admin/?page=berita'),
            'icon' => '📰',
            'internal' => true,
        ],
        [
            'key' => 'rakerdinma',
            'label' => 'Peserta RAKERDINMA',
            'desc' => 'Data peserta, filter, export, sertifikat',
            'url' => url('pesertakerdinma/?page=dashboard'),
            'icon' => '📋',
            'internal' => false,
        ],
        [
            'key' => 'pemesanan',
            'label' => 'Pemesanan Layanan',
            'desc' => 'MOPDIK, Batik, Buku Ke-NU-an, Aswaja',
            'url' => url('adminpemesananbuku/?page=dashboard'),
            'icon' => '📚',
            'internal' => false,
        ],
        [
            'key' => 'pengkinian',
            'label' => 'Pengkinian Data',
            'desc' => 'Data satuan pendidikan & kontak',
            'url' => url('adminpengkinian/?page=dashboard'),
            'icon' => '📱',
            'internal' => false,
        ],
        [
            'key' => 'distribusi',
            'label' => 'Distribusi LKPD',
            'desc' => 'Tracking pengiriman buku LKPD',
            'url' => url('admindistribusi/?page=dashboard'),
            'icon' => '📦',
            'internal' => false,
        ],
    ];
}

function getAdminHubStats(): array
{
    $stats = [
        'berita' => ['total' => 0, 'published' => 0, 'draft' => 0],
        'peserta' => 0,
        'pemesanan' => 0,
        'pengkinian' => 0,
        'distribusi' => 0,
    ];

    try {
        $stats['berita'] = countBeritaByStatus();
    } catch (Throwable) {
    }

    try {
        $pdo = getDb();
        if ($pdo->query("SHOW TABLES LIKE 'peserta_rakerdinma'")->fetch()) {
            $stats['peserta'] = (int) $pdo->query('SELECT COUNT(*) FROM peserta_rakerdinma')->fetchColumn();
        }
        if ($pdo->query("SHOW TABLES LIKE 'pemesanan'")->fetch()) {
            $stats['pemesanan'] = (int) $pdo->query('SELECT COUNT(*) FROM pemesanan')->fetchColumn();
        } elseif ($pdo->query("SHOW TABLES LIKE 'pemesanan_buku'")->fetch()) {
            $stats['pemesanan'] = (int) $pdo->query('SELECT COUNT(*) FROM pemesanan_buku')->fetchColumn();
        }
        if ($pdo->query("SHOW TABLES LIKE 'pengkinian_data_satuan'")->fetch()) {
            $stats['pengkinian'] = (int) $pdo->query('SELECT COUNT(*) FROM pengkinian_data_satuan')->fetchColumn();
        }
        if ($pdo->query("SHOW TABLES LIKE 'distribusi_lkpd_satuan'")->fetch()) {
            $stats['distribusi'] = (int) $pdo->query('SELECT COUNT(*) FROM distribusi_lkpd_satuan')->fetchColumn();
        }
    } catch (Throwable) {
    }

    return $stats;
}
