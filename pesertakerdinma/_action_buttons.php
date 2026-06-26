<?php

declare(strict_types=1);

/** @var array $row @var array $filters @var string $search */
$id = (int) ($row['id'] ?? 0);
?>
<div class="flex items-center justify-center gap-1">
  <a href="<?= url('pesertakerdinma/?page=detail&id=' . $id) ?>"
     title="Detail" aria-label="Detail"
     class="inline-flex p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
    </svg>
  </a>
  <a href="<?= url('pesertakerdinma/?page=form&id=' . $id) ?>"
     title="Edit" aria-label="Edit"
     class="inline-flex p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
    </svg>
  </a>
  <form method="post" class="inline" onsubmit="return confirm('Hapus peserta ini?');">
    <input type="hidden" name="delete_id" value="<?= $id ?>">
    <input type="hidden" name="redirect" value="list">
    <?php foreach ($filters as $k => $v): if ($v !== ''): ?>
      <input type="hidden" name="filter_<?= sanitize($k) ?>" value="<?= sanitize($v) ?>">
    <?php endif; endforeach; ?>
    <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= sanitize($search) ?>"><?php endif; ?>
    <button type="submit" title="Hapus" aria-label="Hapus"
            class="inline-flex p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
      </svg>
    </button>
  </form>
</div>
