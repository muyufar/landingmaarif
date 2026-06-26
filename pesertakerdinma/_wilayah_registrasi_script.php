<script>
(function () {
  const WILAYAH_API = <?= json_encode(url('rakerdinma/wilayah.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const KABUPATEN_CODE = <?= json_encode(defaultWilayahMagelang()['kode_kabupaten'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const savedKecamatan = <?= json_encode($formData['kode_kecamatan'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  const select = document.getElementById('kode_kecamatan');
  const nameInput = document.getElementById('nama_kecamatan');
  if (!select || !nameInput) return;

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

  function fillSelect(items, selectedCode) {
    select.innerHTML = '';
    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = '-- Pilih Kecamatan --';
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

  function syncName() {
    const opt = select.options[select.selectedIndex];
    nameInput.value = opt && opt.dataset.name ? opt.dataset.name : '';
  }

  select.addEventListener('change', syncName);

  fetchWilayah('districts', KABUPATEN_CODE)
    .then(function (items) {
      fillSelect(items, savedKecamatan);
      syncName();
    })
    .catch(function () {
      alert('Gagal memuat data kecamatan. Periksa koneksi internet.');
    });
})();
</script>
