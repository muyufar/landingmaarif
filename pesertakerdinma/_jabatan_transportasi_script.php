<script>
(function () {
  document.querySelectorAll('[data-choice-target]').forEach(function (select) {
    var wrap = document.getElementById(select.getAttribute('data-choice-target'));
    if (!wrap) return;
    var input = wrap.querySelector('input');
    function toggle() {
      var show = select.value === 'Lainnya';
      wrap.classList.toggle('hidden', !show);
      if (input) {
        input.required = show;
        if (!show) input.value = '';
      }
    }
    select.addEventListener('change', toggle);
    toggle();
  });
})();
</script>
