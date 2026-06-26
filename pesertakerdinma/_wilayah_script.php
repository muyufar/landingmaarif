<script>
(function () {
  const WILAYAH_API = <?= json_encode(url('rakerdinma/wilayah.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const saved = {
    kode_provinsi: <?= json_encode($formData['kode_provinsi'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    kode_kabupaten: <?= json_encode($formData['kode_kabupaten'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    kode_kecamatan: <?= json_encode($formData['kode_kecamatan'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    kode_kelurahan: <?= json_encode($formData['kode_kelurahan'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
  };
  const selects = {
    provinsi: document.getElementById('kode_provinsi'),
    kabupaten: document.getElementById('kode_kabupaten'),
    kecamatan: document.getElementById('kode_kecamatan'),
    kelurahan: document.getElementById('kode_kelurahan'),
  };
  const names = {
    provinsi: document.getElementById('nama_provinsi'),
    kabupaten: document.getElementById('nama_kabupaten'),
    kecamatan: document.getElementById('nama_kecamatan'),
    kelurahan: document.getElementById('nama_kelurahan'),
  };
  async function fetchWilayah(level, code) {
    let u = WILAYAH_API + '?level=' + encodeURIComponent(level);
    if (code) u += '&code=' + encodeURIComponent(code);
    const res = await fetch(u);
    if (!res.ok) throw new Error('fail');
    return (await res.json()).data || [];
  }
  function fillSelect(select, items, placeholder, selectedCode) {
    select.innerHTML = '';
    const def = document.createElement('option');
    def.value = '';
    def.textContent = placeholder;
    select.appendChild(def);
    items.forEach(function (item) {
      const opt = document.createElement('option');
      opt.value = item.code;
      opt.textContent = item.name;
      opt.dataset.name = item.name;
      if (selectedCode && item.code === selectedCode) opt.selected = true;
      select.appendChild(opt);
    });
  }
  function syncName(select, nameInput) {
    const opt = select.options[select.selectedIndex];
    nameInput.value = opt && opt.dataset.name ? opt.dataset.name : '';
  }
  function resetFrom(level) {
    if (level === 'provinsi') {
      fillSelect(selects.kabupaten, [], '-- Pilih Kabupaten/Kota --', '');
      selects.kabupaten.disabled = true;
      names.kabupaten.value = '';
    }
    if (level === 'provinsi' || level === 'kabupaten') {
      fillSelect(selects.kecamatan, [], '-- Pilih Kecamatan --', '');
      selects.kecamatan.disabled = true;
      names.kecamatan.value = '';
    }
    if (level !== 'kelurahan') {
      fillSelect(selects.kelurahan, [], '-- Pilih Kelurahan/Desa --', '');
      selects.kelurahan.disabled = true;
      names.kelurahan.value = '';
    }
  }
  selects.provinsi.addEventListener('change', async function () {
    syncName(selects.provinsi, names.provinsi);
    resetFrom('provinsi');
    if (!this.value) return;
    const items = await fetchWilayah('regencies', this.value);
    fillSelect(selects.kabupaten, items, '-- Pilih Kabupaten/Kota --', '');
    selects.kabupaten.disabled = false;
  });
  selects.kabupaten.addEventListener('change', async function () {
    syncName(selects.kabupaten, names.kabupaten);
    resetFrom('kabupaten');
    if (!this.value) return;
    const items = await fetchWilayah('districts', this.value);
    fillSelect(selects.kecamatan, items, '-- Pilih Kecamatan --', '');
    selects.kecamatan.disabled = false;
  });
  selects.kecamatan.addEventListener('change', async function () {
    syncName(selects.kecamatan, names.kecamatan);
    resetFrom('kecamatan');
    if (!this.value) return;
    const items = await fetchWilayah('villages', this.value);
    fillSelect(selects.kelurahan, items, '-- Pilih Kelurahan/Desa --', '');
    selects.kelurahan.disabled = false;
  });
  selects.kelurahan.addEventListener('change', function () {
    syncName(selects.kelurahan, names.kelurahan);
  });
  (async function init() {
    try {
      fillSelect(selects.provinsi, await fetchWilayah('provinces'), '-- Pilih Provinsi --', saved.kode_provinsi);
      syncName(selects.provinsi, names.provinsi);
      if (saved.kode_provinsi) {
        fillSelect(selects.kabupaten, await fetchWilayah('regencies', saved.kode_provinsi), '-- Pilih Kabupaten/Kota --', saved.kode_kabupaten);
        selects.kabupaten.disabled = false;
        syncName(selects.kabupaten, names.kabupaten);
      }
      if (saved.kode_kabupaten) {
        fillSelect(selects.kecamatan, await fetchWilayah('districts', saved.kode_kabupaten), '-- Pilih Kecamatan --', saved.kode_kecamatan);
        selects.kecamatan.disabled = false;
        syncName(selects.kecamatan, names.kecamatan);
      }
      if (saved.kode_kecamatan) {
        fillSelect(selects.kelurahan, await fetchWilayah('villages', saved.kode_kecamatan), '-- Pilih Kelurahan/Desa --', saved.kode_kelurahan);
        selects.kelurahan.disabled = false;
        syncName(selects.kelurahan, names.kelurahan);
      }
    } catch (e) {
      alert('Gagal memuat data wilayah.');
    }
  })();
})();
</script>
