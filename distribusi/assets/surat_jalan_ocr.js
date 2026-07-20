/**
 * OCR surat jalan LKPD MI — isi otomatis jumlah buku per kelas.
 */
function parseSuratJalanOcrClient(text) {
  const lines = String(text || '').toUpperCase().split(/\r?\n/);
  const kelas = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0 };
  const guru = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0 };
  const found = { 1: false, 2: false, 3: false, 4: false, 5: false, 6: false };
  const foundGuru = { 1: false, 2: false, 3: false, 4: false, 5: false, 6: false };
  const subjectKeys = [
    'BAHASA INDONESIA', 'PENDIDIKAN PANCASILA', 'PENDIDAN PANCASILA', 'PENDIDIKAN',
    'MATEMATIKA', 'BAHASA JAWA', 'BAHASA INGGRIS', 'AGAMA', 'IPAS',
  ];

  function cleanLine(line) {
    return line.trim().replace(/\s+/g, ' ');
  }

  function lastQty(line) {
    const nums = line.match(/\b(\d{1,4})\b/g);
    if (!nums || !nums.length) return 0;
    const qty = parseInt(nums[nums.length - 1], 10);
    return qty > 0 && qty <= 9999 ? qty : 0;
  }

  for (const raw of lines) {
    const line = cleanLine(raw);
    if (!line) continue;

    if (line.includes('BUKU GURU')) {
      for (let k = 1; k <= 6; k++) {
        if (!new RegExp('\\b' + k + '\\b').test(line)) continue;
        const qty = lastQty(line);
        if (qty > 0) {
          guru[k] = qty;
          foundGuru[k] = true;
        }
      }
      continue;
    }

    const hasSubject = subjectKeys.some((key) => line.includes(key));
    if (!hasSubject) continue;

    for (let k = 1; k <= 6; k++) {
      const kelasRe = new RegExp('\\b' + k + '\\b');
      if (!kelasRe.test(line)) continue;
      const qty = lastQty(line);
      if (qty > 0) {
        kelas[k] = qty;
        found[k] = true;
      }
    }
  }

  for (let k = 1; k <= 6; k++) {
    if (found[k]) continue;
    for (const raw of lines) {
      const line = cleanLine(raw);
      if (!/\b(KELAS|KLS)\b/.test(line)) continue;
      if (!new RegExp('\\b' + k + '\\b').test(line)) continue;
      const qty = lastQty(line);
      if (qty > 0) {
        kelas[k] = qty;
        found[k] = true;
        break;
      }
    }
  }

  const detected = Object.values(found).filter(Boolean).length;
  const detectedGuru = Object.values(foundGuru).filter(Boolean).length;
  return { kelas, guru, detected, detectedGuru, ok: detected > 0 || detectedGuru > 0 };
}

async function runSuratJalanOcrFromFile(file, statusEl, formEl) {
  if (!file || !window.Tesseract) {
    if (statusEl) {
      statusEl.textContent = 'OCR tidak tersedia. Isi jumlah buku secara manual.';
      statusEl.className = 'text-xs text-amber-700 mt-2';
    }
    return;
  }

  if (statusEl) {
    statusEl.textContent = 'Membaca dokumen surat jalan...';
    statusEl.className = 'text-xs text-gray-600 mt-2';
  }

  try {
    const result = await Tesseract.recognize(file, 'ind+eng', {
      logger(m) {
        if (statusEl && m.status === 'recognizing text') {
          const pct = Math.round((m.progress || 0) * 100);
          statusEl.textContent = 'Membaca dokumen... ' + pct + '%';
        }
      },
    });

    const parsed = parseSuratJalanOcrClient(result.data.text || '');
    if (!formEl) return parsed;

    for (let i = 1; i <= 6; i++) {
      const input = formEl.querySelector('[name="terima_kelas_' + i + '"]');
      if (input && parsed.kelas[i] > 0) {
        input.value = String(parsed.kelas[i]);
      }
      const guruInput = formEl.querySelector('[name="terima_guru_kelas_' + i + '"]');
      if (guruInput && parsed.guru[i] > 0) {
        guruInput.value = String(parsed.guru[i]);
      }
    }

    if (statusEl) {
      if (parsed.ok) {
        const parts = [];
        if (parsed.detected > 0) parts.push(parsed.detected + ' kelas LKPD');
        if (parsed.detectedGuru > 0) parts.push(parsed.detectedGuru + ' buku guru');
        statusEl.textContent =
          'Berhasil membaca ' + parts.join(' + ') + '. Periksa dan koreksi jika perlu sebelum simpan.';
        statusEl.className = 'text-xs text-green-700 mt-2';
      } else {
        statusEl.textContent =
          'Sistem tidak dapat membaca jumlah buku dari foto. Silakan isi manual di bawah.';
        statusEl.className = 'text-xs text-amber-700 mt-2';
      }
    }

    return parsed;
  } catch (err) {
    if (statusEl) {
      statusEl.textContent = 'Gagal membaca foto. Isi jumlah buku secara manual.';
      statusEl.className = 'text-xs text-red-700 mt-2';
    }
    return { kelas: {}, detected: 0, ok: false };
  }
}

function initSuratJalanOcr() {
  const form = document.getElementById('form-terima-sj');
  const fileInput = document.getElementById('file_surat_jalan_distributor');
  const statusEl = document.getElementById('ocr-status');
  const btnOcr = document.getElementById('btn-baca-ulang-ocr');

  if (!form || !fileInput) return;

  const run = () => {
    const file = fileInput.files && fileInput.files[0];
    if (!file) return;
    if (!/^image\//i.test(file.type)) {
      if (statusEl) {
        statusEl.textContent = 'OCR otomatis untuk foto/gambar. PDF tetap bisa diunggah, isi manual jumlah buku.';
        statusEl.className = 'text-xs text-gray-600 mt-2';
      }
      return;
    }
    runSuratJalanOcrFromFile(file, statusEl, form);
  };

  fileInput.addEventListener('change', run);
  if (btnOcr) {
    btnOcr.addEventListener('click', run);
  }
}

document.addEventListener('DOMContentLoaded', initSuratJalanOcr);
