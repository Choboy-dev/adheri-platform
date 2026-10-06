<?php

declare(strict_types=1);

/**
 * Gmail-style formatting strip (sits above the Send row when toggled).
 */
?>
<div class="gmail-format-bar" data-mail-toolbar hidden>
  <div class="gmail-format-group">
    <button type="button" class="gmail-format-btn" data-cmd="undo" title="Undo" aria-label="Undo">
      <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
    </button>
    <button type="button" class="gmail-format-btn" data-cmd="redo" title="Redo" aria-label="Redo">
      <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
    </button>
  </div>

  <span class="gmail-format-sep" aria-hidden="true"></span>

  <div class="gmail-format-group gmail-format-select-wrap">
    <select class="gmail-format-select" data-cmd="fontName" title="Font" aria-label="Font">
      <option value="Arial" selected>Sans Serif</option>
      <option value="Georgia">Serif</option>
      <option value="Courier New">Fixed Width</option>
      <option value="Comic Sans MS">Wide</option>
      <option value="Tahoma">Tahoma</option>
      <option value="Verdana">Verdana</option>
    </select>
  </div>

  <span class="gmail-format-sep" aria-hidden="true"></span>

  <div class="gmail-format-group gmail-format-select-wrap">
    <select class="gmail-format-select gmail-format-size" data-cmd="fontSize" title="Size" aria-label="Size">
      <option value="1">Small</option>
      <option value="3" selected>Normal</option>
      <option value="5">Large</option>
      <option value="7">Huge</option>
    </select>
    <span class="gmail-format-size-face" aria-hidden="true">
      <span class="gmail-tt-lg">T</span><span class="gmail-tt-sm">T</span>
    </span>
  </div>

  <span class="gmail-format-sep" aria-hidden="true"></span>

  <div class="gmail-format-group">
    <button type="button" class="gmail-format-btn" data-cmd="bold" title="Bold" aria-label="Bold"><strong>B</strong></button>
    <button type="button" class="gmail-format-btn" data-cmd="italic" title="Italic" aria-label="Italic"><em>I</em></button>
    <button type="button" class="gmail-format-btn" data-cmd="underline" title="Underline" aria-label="Underline"><span class="gmail-u">U</span></button>
    <label class="gmail-format-btn gmail-format-color" title="Text color">
      <span class="gmail-color-a" aria-hidden="true">A</span>
      <input type="color" data-cmd="foreColor" value="#202124" aria-label="Text color">
    </label>
  </div>

  <span class="gmail-format-sep" aria-hidden="true"></span>

  <div class="gmail-format-group mail-pop-wrap">
    <button type="button" class="gmail-format-btn" data-mail-pop-toggle="align" title="Align" aria-label="Align">
      <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 5h18v2H3V5zm0 6h12v2H3v-2zm0 6h18v2H3v-2z"/></svg>
      <svg class="gmail-caret" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
    </button>
    <div class="mail-pop gmail-mini-pop" data-mail-pop="align" hidden>
      <button type="button" data-cmd="justifyLeft">Align left</button>
      <button type="button" data-cmd="justifyCenter">Align center</button>
      <button type="button" data-cmd="justifyRight">Align right</button>
    </div>
  </div>

  <div class="gmail-format-group">
    <button type="button" class="gmail-format-btn" data-cmd="insertOrderedList" title="Numbered list" aria-label="Numbered list">
      <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
    </button>
    <button type="button" class="gmail-format-btn" data-cmd="insertUnorderedList" title="Bulleted list" aria-label="Bulleted list">
      <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
    </button>
    <button type="button" class="gmail-format-btn" data-cmd="outdent" title="Decrease indent" aria-label="Decrease indent">
      <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M11 17h10v-2H11v2zm-8-5l4 4V8l-4 4zm0 9h18v-2H3v2zM3 3v2h18V3H3zm8 6h10V7H11v2zm0 4h10v-2H11v2z"/></svg>
    </button>
    <button type="button" class="gmail-format-btn" data-cmd="indent" title="Increase indent" aria-label="Increase indent">
      <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 21h18v-2H3v2zM3 8v8l4-4-4-4zm8 9h10v-2H11v2zM3 3v2h18V3H3zm8 6h10V7H11v2zm0 4h10v-2H11v2z"/></svg>
    </button>
  </div>

  <span class="gmail-format-sep" aria-hidden="true"></span>

  <div class="gmail-format-group mail-pop-wrap">
    <button type="button" class="gmail-format-btn" data-mail-pop-toggle="format-more" title="More formatting options" aria-label="More formatting options">
      <svg class="gmail-caret" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
    </button>
    <div class="mail-pop gmail-mini-pop" data-mail-pop="format-more" hidden>
      <button type="button" data-cmd="strikeThrough">Strikethrough</button>
      <label class="gmail-pop-color">Highlight <input type="color" data-cmd="hiliteColor" value="#fff59d"></label>
      <button type="button" data-cmd="formatBlock" data-value="blockquote">Quote</button>
      <button type="button" data-cmd="unlink">Remove link</button>
      <button type="button" data-cmd="removeFormat">Clear formatting</button>
      <button type="button" data-cmd="insertImage">Insert image</button>
    </div>
  </div>
</div>
