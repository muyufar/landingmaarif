<script>
(function () {
  const WILAYAH_API = <?= json_encode(url('rakerdinma/wilayah.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const KABUPATEN_CODE = <?= json_encode(defaultWilayahMagelang()['kode_kabupaten'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const saved = {
    kecamatan: <?= json_encode($formData['kode_kecamatan'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    kelurahan: <?= json_encode($formData['kode_kelurahan'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
  };

  const selects = {
    kecamatan: document.getElementById('kode_kecamatan'),
    kelurahan: document.getElementById('kode_kelurahan'),
  };
  const names = {
    kecamatan: document.getElementById('nama_kecamatan'),
    kelurahan: document.getElementById('nama_kelurahan'),
  };

  if (!selects.kecamatan || !selects.kelurahan) return;

  async function fetchWilayah(level, code) {
    let apiUrl = WILAYAH_API + '?level=' + encodeURIComponent(level);
    if (code) {
      apiUrl += '&code=' + encodeURIComponent(code);
    }
    const res = await fetch(apiUrl);
    if (!res.ok) {
      throw new Error('Gagal memuat data wilayah');
    }
    const json = await res.json();
    return json.data || [];
  }

  function fillSelect(select, items, placeholder, selectedCode) {
    select.innerHTML = '';
    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = placeholder;
    select.appendChild(defaultOpt);

    items.forEach(function (item) {
      const opt = document.createElement('option');
      opt.value = item.code;
      opt.textContent = item.name;
      opt.dataset.name = item.name;
      if (selectedCode && item.code === selectedCode) {
        opt.selected = true;
      }
      select.appendChild(opt);
    });
  }

  function syncName(select, nameInput) {
    const opt = select.options[select.selectedIndex];
    nameInput.value = opt && opt.dataset.name ? opt.dataset.name : '';
  }

  function resetKelurahan() {
    fillSelect(selects.kelurahan, [], '-- Pilih Desa/Kelurahan --', '');
    selects.kelurahan.disabled = true;
    names.kelurahan.value = '';
  }

  selects.kecamatan.addEventListener('change', async function () {
    syncName(selects.kecamatan, names.kecamatan);
    resetKelurahan();
    if (!this.value) return;

    selects.kelurahan.disabled = true;
    try {
      const items = await fetchWilayah('villages', this.value);
      fillSelect(selects.kelurahan, items, '-- Pilih Desa/Kelurahan --', '');
      selects.kelurahan.disabled = false;
    } catch (e) {
      alert('Gagal memuat data desa/kelurahan.');
    }
  });

  selects.kelurahan.addEventListener('change', function () {
    syncName(selects.kelurahan, names.kelurahan);
  });

  async function initWilayah() {
    try {
      const districts = await fetchWilayah('districts', KABUPATEN_CODE);
      fillSelect(selects.kecamatan, districts, '-- Pilih Kecamatan --', saved.kecamatan);
      syncName(selects.kecamatan, names.kecamatan);

      if (saved.kecamatan) {
        const villages = await fetchWilayah('villages', saved.kecamatan);
        fillSelect(selects.kelurahan, villages, '-- Pilih Desa/Kelurahan --', saved.kelurahan);
        selects.kelurahan.disabled = false;
        syncName(selects.kelurahan, names.kelurahan);
      }
    } catch (e) {
      alert('Gagal memuat data kecamatan. Periksa koneksi internet.');
    }
  }

  initWilayah();
})();
</script>
