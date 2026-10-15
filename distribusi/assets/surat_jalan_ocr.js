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

function applyTerimaQuickFillForKelas(form, kelas, checked) {
  const k = String(kelas);
  const siswaInput = form.querySelector('[name="terima_kelas_' + k + '"]');
  if (siswaInput) {
    const fill = parseInt(siswaInput.getAttribute('data-fill-siswa') || '0', 10);
    if (fill > 0) {
      siswaInput.value = checked ? String(fill) : '0';
    }
  }
  const guruInput = form.querySelector('[name="terima_guru_kelas_' + k + '"]');
  if (guruInput) {
    const fillG = parseInt(guruInput.getAttribute('data-fill-guru') || '0', 10);
    if (fillG > 0) {
      guruInput.value = checked ? String(fillG) : '0';
    }
  }
}

function syncTerimaQuickCheckboxes(form) {
  const master = document.getElementById('terima-semua-sesuai-kirim');
  if (!master) return;
  const kelasChecks = form.querySelectorAll('.terima-kelas-quick');
  const siswaChecks = form.querySelectorAll('.terima-siswa-quick');
  const guruChecks = form.querySelectorAll('.terima-guru-quick');
  let allOn = true;
  kelasChecks.forEach(function (cb) {
    if (!cb.checked) allOn = false;
  });
  siswaChecks.forEach(function (cb) {
    if (!cb.checked && parseInt(cb.closest('div')?.querySelector('[data-fill-siswa]')?.getAttribute('data-fill-siswa') || '0', 10) > 0) {
      allOn = false;
    }
  });
  guruChecks.forEach(function (cb) {
    if (!cb.checked) allOn = false;
  });
  master.checked = kelasChecks.length > 0 ? allOn : master.checked;
}

function initTerimaQuickFill() {
  const form = document.getElementById('form-terima-sj');
  if (!form) return;

  const master = document.getElementById('terima-semua-sesuai-kirim');

  function setAll(checked) {
    form.querySelectorAll('.terima-kelas-quick, .terima-siswa-quick, .terima-guru-quick').forEach(function (cb) {
      cb.checked = checked;
    });
    for (let k = 1; k <= 6; k++) {
      applyTerimaQuickFillForKelas(form, k, checked);
    }
  }

  if (master) {
    master.addEventListener('change', function () {
      setAll(master.checked);
    });
  }

  form.querySelectorAll('.terima-kelas-quick').forEach(function (cb) {
    cb.addEventListener('change', function () {
      const k = cb.getAttribute('data-kelas');
      applyTerimaQuickFillForKelas(form, k, cb.checked);
      const siswaQuick = form.querySelector('.terima-siswa-quick[data-kelas="' + k + '"]');
      const guruQuick = form.querySelector('.terima-guru-quick[data-kelas="' + k + '"]');
      if (siswaQuick) siswaQuick.checked = cb.checked;
      if (guruQuick) guruQuick.checked = cb.checked;
      syncTerimaQuickCheckboxes(form);
    });
  });

  form.querySelectorAll('.terima-siswa-quick').forEach(function (cb) {
    cb.addEventListener('change', function () {
      const k = cb.getAttribute('data-kelas');
      const input = form.querySelector('[name="terima_kelas_' + k + '"]');
      if (input) {
        const fill = parseInt(input.getAttribute('data-fill-siswa') || '0', 10);
        input.value = cb.checked && fill > 0 ? String(fill) : '0';
      }
      syncTerimaQuickCheckboxes(form);
    });
  });

  form.querySelectorAll('.terima-guru-quick').forEach(function (cb) {
    cb.addEventListener('change', function () {
      const k = cb.getAttribute('data-kelas');
      const input = form.querySelector('[name="terima_guru_kelas_' + k + '"]');
      if (input) {
        const fill = parseInt(input.getAttribute('data-fill-guru') || '0', 10);
        input.value = cb.checked && fill > 0 ? String(fill) : '0';
      }
      syncTerimaQuickCheckboxes(form);
    });
  });

  form.querySelectorAll('.terima-kelas-input, .terima-guru-input').forEach(function (input) {
    input.addEventListener('input', function () {
      const name = input.getAttribute('name') || '';
      let k = null;
      if (name.indexOf('terima_kelas_') === 0) {
        k = name.replace('terima_kelas_', '');
        const fill = parseInt(input.getAttribute('data-fill-siswa') || '0', 10);
        const quick = form.querySelector('.terima-siswa-quick[data-kelas="' + k + '"]');
        if (quick) quick.checked = fill > 0 && parseInt(input.value || '0', 10) === fill;
      } else if (name.indexOf('terima_guru_kelas_') === 0) {
        k = name.replace('terima_guru_kelas_', '');
        const fill = parseInt(input.getAttribute('data-fill-guru') || '0', 10);
        const quick = form.querySelector('.terima-guru-quick[data-kelas="' + k + '"]');
        if (quick) quick.checked = fill > 0 && parseInt(input.value || '0', 10) === fill;
      }
      if (k) {
        const kelasCb = form.querySelector('.terima-kelas-quick[data-kelas="' + k + '"]');
        if (kelasCb) {
          const siswaInput = form.querySelector('[name="terima_kelas_' + k + '"]');
          const guruInput = form.querySelector('[name="terima_guru_kelas_' + k + '"]');
          const fs = parseInt(siswaInput?.getAttribute('data-fill-siswa') || '0', 10);
          const fg = parseInt(guruInput?.getAttribute('data-fill-guru') || '0', 10);
          const vs = parseInt(siswaInput?.value || '0', 10);
          const vg = guruInput ? parseInt(guruInput.value || '0', 10) : 0;
          kelasCb.checked = (fs <= 0 || vs === fs) && (fg <= 0 || vg === fg) && (fs > 0 || fg > 0);
        }
      }
      syncTerimaQuickCheckboxes(form);
    });
  });
}

document.addEventListener('DOMContentLoaded', function () {
  initSuratJalanOcr();
  initTerimaQuickFill();
});
