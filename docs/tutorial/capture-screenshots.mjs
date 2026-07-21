/**
 * Capture live screenshots for distribusi LKPD tutorial.
 * Run: node capture-screenshots.mjs
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(__dirname, 'screenshots');
const BASE = process.env.TUTORIAL_BASE_URL || 'http://localhost/maarifnu';
const ADMIN_PASS = process.env.TUTORIAL_ADMIN_PASS || 'rakerdinma2026';
const PETUGAS_USER = process.env.TUTORIAL_PETUGAS_USER || 'panji';
const PETUGAS_PASS = process.env.TUTORIAL_PETUGAS_PASS || 'tutorial2026';

fs.mkdirSync(OUT, { recursive: true });

const shots = [];

function absUrl(href) {
  if (!href) return null;
  if (href.startsWith('http')) return href;
  const origin = new URL(BASE).origin;
  if (href.startsWith('/')) return `${origin}${href}`;
  return `${BASE.replace(/\/$/, '')}/${href}`;
}

async function snap(page, id, name, url, opts = {}) {
  const file = `${String(shots.length + 1).padStart(2, '0')}-${id}.png`;
  const full = path.join(OUT, file);
  await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
  if (opts.waitMs) await page.waitForTimeout(opts.waitMs);
  if (opts.selector) {
    try {
      await page.waitForSelector(opts.selector, { timeout: 15000 });
    } catch {
      /* continue */
    }
  }
  await page.screenshot({
    path: full,
    fullPage: opts.fullPage === true,
    ...(opts.clip ? { clip: opts.clip } : {}),
  });
  shots.push({ id, name, file, url });
  console.log('OK', file, name);
}

async function main() {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext({
    viewport: { width: 1360, height: 900 },
    locale: 'id-ID',
  });
  const page = await ctx.newPage();

  // --- ADMIN ---
  await snap(page, 'admin-login', 'Login Super Admin', `${BASE}/admindistribusi/`);

  await page.goto(`${BASE}/admindistribusi/`, { waitUntil: 'networkidle' });
  await page.fill('input[name="admin_password"]', ADMIN_PASS);
  await page.click('button[type="submit"]');
  await page.waitForURL(/page=dashboard/, { timeout: 30000 });

  await snap(page, 'admin-dashboard', 'Dashboard Admin', `${BASE}/admindistribusi/?page=dashboard`);
  await snap(page, 'admin-import', 'Import Data Excel', `${BASE}/admindistribusi/?page=import`);
  await snap(page, 'admin-list', 'Monitoring Satuan', `${BASE}/admindistribusi/?page=list`, { fullPage: false });

  // Open first detail link if exists
  const detailHref = await page.locator('a[href*="page=detail"]').first().getAttribute('href').catch(() => null);
  if (detailHref) {
  await snap(page, 'admin-detail', 'Detail Satuan (Admin)', absUrl(detailHref), { fullPage: false });
  } else {
    await snap(page, 'admin-detail', 'Detail Satuan (Admin)', `${BASE}/admindistribusi/?page=detail&id=1`, { fullPage: false });
  }

  await snap(page, 'admin-petugas', 'Kelola Akun Petugas', `${BASE}/admindistribusi/?page=petugas`);

  await page.goto(`${BASE}/admindistribusi/?logout=1`, { waitUntil: 'networkidle' });

  // --- PETUGAS ---
  await snap(page, 'petugas-login', 'Login Petugas Distribusi', `${BASE}/distribusi/`);

  await page.goto(`${BASE}/distribusi/`, { waitUntil: 'networkidle' });
  await page.fill('input[name="login_username"]', PETUGAS_USER);
  await page.fill('input[name="login_password"]', PETUGAS_PASS);
  await page.click('button[type="submit"]');
  await page.waitForURL(/page=dashboard/, { timeout: 30000 }).catch(async () => {
    console.warn('Petugas login may have failed — check TUTORIAL_PETUGAS_PASS');
  });

  await snap(page, 'petugas-dashboard', 'Dashboard Petugas', `${BASE}/distribusi/?page=dashboard`);
  await snap(page, 'petugas-kirim', 'Kirim Buku', `${BASE}/distribusi/?page=kirim`, { fullPage: false });

  // Terima — pick delivery satuan if dropdown has options
  await page.goto(`${BASE}/distribusi/?page=terima`, { waitUntil: 'networkidle' });
  const options = page.locator('select[name="npsn"] option');
  const count = await options.count();
  if (count > 1) {
    const val = await options.nth(1).getAttribute('value');
    if (val) {
      await page.goto(`${BASE}/distribusi/?page=terima&npsn=${encodeURIComponent(val)}`, { waitUntil: 'networkidle' });
    }
  }
  await page.setViewportSize({ width: 1360, height: 1400 });
  await snap(page, 'petugas-terima', 'Terima Buku & Upload Surat Jalan', page.url(), { fullPage: false });
  await page.setViewportSize({ width: 1360, height: 900 });

  await snap(page, 'petugas-list', 'List Satuan (Petugas)', `${BASE}/distribusi/?page=list`, { fullPage: false });

  const pDetail = await page.locator('a[href*="page=detail"]').first().getAttribute('href').catch(() => null);
  if (pDetail) {
    await snap(page, 'petugas-detail', 'Detail Satuan (Petugas)', absUrl(pDetail), { fullPage: false });
  }

  // Public dashboard entry
  await page.goto(`${BASE}/dashboard/`, { waitUntil: 'networkidle' });
  await snap(page, 'portal-entry', 'Akses dari Dashboard Layanan', `${BASE}/dashboard/`, { fullPage: true });

  await browser.close();

  fs.writeFileSync(path.join(__dirname, 'screenshots.json'), JSON.stringify({ base: BASE, shots }, null, 2));
  console.log(`\nCaptured ${shots.length} screenshots.`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
