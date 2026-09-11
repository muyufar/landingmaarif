(function () {
  'use strict';

  var cfg = window.TWIBBON_CONFIG;
  if (!cfg) return;

  var SIZE = cfg.size;
  var CX = cfg.photoCx;
  var CY = cfg.photoCy;
  var R = cfg.photoR;

  var photoInput = document.getElementById('photo-input');
  var fileNameEl = document.getElementById('file-name');
  var editorSection = document.getElementById('editor-section');
  var downloadSection = document.getElementById('download-section');
  var previewCanvas = document.getElementById('preview-canvas');
  var previewWrap = document.getElementById('preview-wrap');
  var zoomRange = document.getElementById('zoom-range');
  var zoomValue = document.getElementById('zoom-value');
  var resetBtn = document.getElementById('reset-btn');
  var downloadBtn = document.getElementById('download-btn');
  var changePhotoBtn = document.getElementById('change-photo-btn');

  var previewCtx = previewCanvas.getContext('2d');
  var exportCanvas = document.createElement('canvas');
  exportCanvas.width = SIZE;
  exportCanvas.height = SIZE;
  var exportCtx = exportCanvas.getContext('2d');

  var templateImg = null;
  var overlayCanvas = null;
  var userPhoto = null;
  var offsetX = 0;
  var offsetY = 0;
  var zoom = 1;
  var dragging = false;
  var dragStartX = 0;
  var dragStartY = 0;
  var dragBaseX = 0;
  var dragBaseY = 0;

  var PREVIEW = previewCanvas.width;

  function loadImage(src) {
    return new Promise(function (resolve, reject) {
      var img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = function () { resolve(img); };
      img.onerror = reject;
      img.src = src;
    });
  }

  function buildOverlay(img) {
    var c = document.createElement('canvas');
    c.width = SIZE;
    c.height = SIZE;
    var ctx = c.getContext('2d');
    ctx.drawImage(img, 0, 0, SIZE, SIZE);
    var data = ctx.getImageData(0, 0, SIZE, SIZE);
    var px = data.data;
    for (var y = 0; y < SIZE; y++) {
      for (var x = 0; x < SIZE; x++) {
        var dx = x - CX;
        var dy = y - CY;
        if (dx * dx + dy * dy > R * R) continue;
        var i = (y * SIZE + x) * 4;
        if (px[i] + px[i + 1] + px[i + 2] < 120) {
          px[i + 3] = 0;
        }
      }
    }
    ctx.putImageData(data, 0, 0);
    return c;
  }

  function coverRect(img, scale, ox, oy) {
    var cover = (2 * R) / Math.min(img.width, img.height) * scale;
    var dw = img.width * cover;
    var dh = img.height * cover;
    return {
      dx: CX - dw / 2 + ox,
      dy: CY - dh / 2 + oy,
      dw: dw,
      dh: dh,
    };
  }

  function render(targetCtx, targetSize) {
    targetCtx.clearRect(0, 0, targetSize, targetSize);
    targetCtx.fillStyle = '#ffffff';
    targetCtx.fillRect(0, 0, targetSize, targetSize);

    if (userPhoto) {
      targetCtx.save();
      targetCtx.beginPath();
      targetCtx.arc(CX, CY, R, 0, Math.PI * 2);
      targetCtx.clip();
      var rect = coverRect(userPhoto, zoom, offsetX, offsetY);
      targetCtx.drawImage(userPhoto, rect.dx, rect.dy, rect.dw, rect.dh);
      targetCtx.restore();
    }

    if (overlayCanvas) {
      targetCtx.drawImage(overlayCanvas, 0, 0, SIZE, SIZE);
    }
  }

  function renderPreview() {
    render(exportCtx, SIZE);
    previewCtx.clearRect(0, 0, PREVIEW, PREVIEW);
    previewCtx.drawImage(exportCanvas, 0, 0, PREVIEW, PREVIEW);
  }

  function resetTransform() {
    offsetX = 0;
    offsetY = 0;
    zoom = 1;
    zoomRange.value = '100';
    zoomValue.textContent = '100%';
    renderPreview();
  }

  function showEditor() {
    editorSection.classList.remove('hidden');
    downloadSection.classList.remove('hidden');
    renderPreview();
  }

  function pointerScale() {
    return SIZE / PREVIEW;
  }

  function onPointerDown(e) {
    if (!userPhoto) return;
    dragging = true;
    dragStartX = e.clientX;
    dragStartY = e.clientY;
    dragBaseX = offsetX;
    dragBaseY = offsetY;
    previewWrap.style.cursor = 'grabbing';
  }

  function onPointerMove(e) {
    if (!dragging) return;
    var s = pointerScale();
    offsetX = dragBaseX + (e.clientX - dragStartX) * s;
    offsetY = dragBaseY + (e.clientY - dragStartY) * s;
    renderPreview();
  }

  function onPointerUp() {
    dragging = false;
    previewWrap.style.cursor = 'grab';
  }

  photoInput.addEventListener('change', function () {
    var file = photoInput.files && photoInput.files[0];
    if (!file) return;

    if (file.size > 8 * 1024 * 1024) {
      alert('Ukuran foto maksimal 8 MB.');
      photoInput.value = '';
      return;
    }

    fileNameEl.textContent = file.name;
    fileNameEl.classList.remove('hidden');

    var reader = new FileReader();
    reader.onload = function (ev) {
      var img = new Image();
      img.onload = function () {
        userPhoto = img;
        resetTransform();
        showEditor();
      };
      img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });

  zoomRange.addEventListener('input', function () {
    zoom = parseInt(zoomRange.value, 10) / 100;
    zoomValue.textContent = zoomRange.value + '%';
    renderPreview();
  });

  resetBtn.addEventListener('click', resetTransform);

  downloadBtn.addEventListener('click', function () {
    if (!userPhoto) return;
    render(exportCtx, SIZE);
    exportCanvas.toBlob(function (blob) {
      if (!blob) return;
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'Twibbon_Harlah97_LP_Maarif_NU.png';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(a.href);
    }, 'image/png');
  });

  changePhotoBtn.addEventListener('click', function () {
    photoInput.value = '';
    photoInput.click();
  });

  previewWrap.addEventListener('mousedown', onPointerDown);
  window.addEventListener('mousemove', onPointerMove);
  window.addEventListener('mouseup', onPointerUp);

  previewWrap.addEventListener('touchstart', function (e) {
    if (e.touches.length === 1) {
      e.preventDefault();
      onPointerDown(e.touches[0]);
    }
  }, { passive: false });
  previewWrap.addEventListener('touchmove', function (e) {
    if (e.touches.length === 1) {
      e.preventDefault();
      onPointerMove(e.touches[0]);
    }
  }, { passive: false });
  previewWrap.addEventListener('touchend', onPointerUp);

  previewWrap.style.cursor = 'grab';

  loadImage(cfg.templateUrl).then(function (img) {
    templateImg = img;
    overlayCanvas = buildOverlay(img);
  }).catch(function () {
    alert('Gagal memuat template twibbon. Muat ulang halaman.');
  });
})();
