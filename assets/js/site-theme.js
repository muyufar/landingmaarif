(function () {
  var STORAGE_KEY = 'maarif-theme';

  function getPreferredTheme() {
    var saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'dark' || saved === 'light') {
      return saved;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    var root = document.documentElement;
    if (theme === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
    localStorage.setItem(STORAGE_KEY, theme);
    updateToggleLabel(theme);
  }

  function updateToggleLabel(theme) {
    var btn = document.getElementById('maarif-theme-toggle');
    if (!btn) {
      return;
    }
    var isDark = theme === 'dark';
    btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    btn.setAttribute('aria-label', isDark ? 'Aktifkan tema terang' : 'Aktifkan tema gelap');
    var icon = btn.querySelector('.maarif-theme-toggle__icon');
    var label = btn.querySelector('.maarif-theme-toggle__label');
    if (icon) {
      icon.textContent = isDark ? '☀️' : '🌙';
    }
    if (label) {
      label.textContent = isDark ? 'Tema terang' : 'Tema gelap';
    }
  }

  function ensureToggle() {
    if (document.getElementById('maarif-theme-toggle')) {
      return;
    }
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'maarif-theme-toggle';
    btn.className = 'maarif-theme-toggle';
    btn.innerHTML =
      '<span class="maarif-theme-toggle__icon" aria-hidden="true">🌙</span>' +
      '<span class="maarif-theme-toggle__label">Tema gelap</span>';
    btn.addEventListener('click', function () {
      var next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
      applyTheme(next);
    });
    document.body.appendChild(btn);
  }

  window.MaarifTheme = {
    apply: applyTheme,
    get: getPreferredTheme,
  };

  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(getPreferredTheme());
    ensureToggle();
  });
})();
