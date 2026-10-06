<?php

/** @var string $icon */
$icon = $icon ?? '';
?>
<?php if ($icon === 'undo'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
<?php elseif ($icon === 'redo'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
<?php elseif ($icon === 'bullet'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M4 10.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm0 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM8 8h12v2H8V8zm0 6h12v2H8v-2z"/></svg>
<?php elseif ($icon === 'ordered'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6h14v2H7V5zm0 14h14v2H7v-2zm0-7h14v2H7v-2z"/></svg>
<?php elseif ($icon === 'alignLeft'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 3h18v2H3V3zm0 4h12v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm0 4h18v2H3v-2z"/></svg>
<?php elseif ($icon === 'alignCenter'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 3h18v2H3V3zm4 4h10v2H7V7zm-4 4h18v2H3v-2zm4 4h10v2H7v-2zm-4 4h18v2H3v-2z"/></svg>
<?php elseif ($icon === 'alignRight'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 3h18v2H3V3zm6 4h12v2H9V7zm-6 4h18v2H3v-2zm6 4h12v2H9v-2zm-6 4h18v2H3v-2z"/></svg>
<?php elseif ($icon === 'link'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M17 7h-3v2h3a3 3 0 0 1 0 6h-3v2h3a5 5 0 0 0 0-10zm-7 2H7a3 3 0 0 0 0 6h3v2H7a5 5 0 0 1 0-10h3v2zm-1 3h6v2H9v-2z"/></svg>
<?php elseif ($icon === 'image'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-4.86 8.86-3 3.87L9 13.14 6 17h12l-3.86-5.14z"/></svg>
<?php elseif ($icon === 'quote'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M6 17h3l2-4V7H5v6h3l-2 4zm8 0h3l2-4V7h-6v6h3l-2 4z"/></svg>
<?php elseif ($icon === 'clear'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3.27 5 2 6.27l6.97 6.97L6.5 19h3l1.57-3.66L16.73 21 18 19.73 3.27 5zM6 5v.18L8.82 8h2.4l-.72 1.68 2.1 2.1L14.21 8H20V5H6z"/></svg>
<?php elseif ($icon === 'featured'): ?>
<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-4.86 8.86-3 3.87L9 13.14 6 17h12l-3.86-5.14z"/></svg>
<?php elseif ($icon === 'table'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 3v18h18V3H3zm8 16H5v-6h6v6zm0-8H5V5h6v6zm8 8h-6v-6h6v6zm0-8h-6V5h6v6z"/></svg>
<?php elseif ($icon === 'rowAbove'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M4 14h16v6H4v-6zm0-8h7v2H4V6zm9 0h7v2h-7V6zm-4.5 1L12 3.5 15.5 7H13v3h-2V7H8.5z"/></svg>
<?php elseif ($icon === 'rowBelow'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M4 4h16v6H4V4zm0 12h7v2H4v-2zm9 0h7v2h-7v-2zm-4.5 3L12 22.5 15.5 19H13v-3h-2v3H8.5z"/></svg>
<?php elseif ($icon === 'colLeft'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14 4h6v16h-6V4zM4 4h2v7H4V4zm0 9h2v7H4v-7zm3-4.5L3.5 12 7 15.5V13h3v-2H7V8.5z"/></svg>
<?php elseif ($icon === 'colRight'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M4 4h6v16H4V4zm14 0h2v7h-2V4zm0 9h2v7h-2v-7zm-3-4.5L21.5 12 17 15.5V13h-3v-2h3V8.5z"/></svg>
<?php elseif ($icon === 'deleteRow'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M4 10h16v6H4v-6zm12.59-5L15 6.59 13.41 5 12 6.41 13.59 8 12 9.59 13.41 11 15 9.41 16.59 11 18 9.59 16.41 8 18 6.41 16.59 5z"/></svg>
<?php elseif ($icon === 'deleteCol'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M10 4h6v16h-6V4zM5 6.41 6.41 5 8 6.59 9.59 5 11 6.41 9.41 8 11 9.59 9.59 11 8 9.41 6.41 11 5 9.59 6.59 8 5 6.41z"/></svg>
<?php elseif ($icon === 'merge'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M5 5h6v6H5V5zm8 0h6v6h-6V5zM5 13h14v6H5v-6zm7-5.5L9.5 10H11v4h2v-4h1.5L12 7.5z"/></svg>
<?php elseif ($icon === 'split'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M5 5h14v6H5V5zm0 8h6v6H5v-6zm8 0h6v6h-6v-6zm1.5-5H13V5.5L15.5 8 13 10.5V9h-1.5L14 6.5z"/></svg>
<?php elseif ($icon === 'headerRow'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 5h18v4H3V5zm0 6h18v8H3v-8zm2 2v4h14v-4H5z"/></svg>
<?php elseif ($icon === 'deleteTable'): ?>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 3h18v18H3V3zm2 2v3h14V5H5zm0 5v9h4v-9H5zm6 0v9h8v-9h-8zm7.3-8.7L17 3.6 15.7 2.3 14.3 3.7 15.6 5l-1.3 1.3 1.4 1.4L17 6.4l1.3 1.3 1.4-1.4L18.4 5l1.3-1.3-1.4-1.4z"/></svg>
<?php endif; ?>
