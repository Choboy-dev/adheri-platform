(() => {
  'use strict';

  function enhance(select) {
    if (select.dataset.lbEnhanced) return;
    select.dataset.lbEnhanced = '1';
    select.style.position = 'absolute';
    select.style.opacity = '0';
    select.style.pointerEvents = 'none';
    select.style.width = '1px';
    select.style.height = '1px';

    const wrap = document.createElement('div');
    wrap.className = 'lb-dropdown';
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'lb-dropdown-trigger';
    const menu = document.createElement('div');
    menu.className = 'lb-dropdown-menu';

    const sync = () => {
      const opt = select.options[select.selectedIndex];
      trigger.textContent = opt ? opt.textContent : '';
      menu.querySelectorAll('.lb-dropdown-option').forEach((b) => {
        b.classList.toggle('is-active', b.dataset.value === select.value);
      });
    };

    Array.from(select.options).forEach((opt) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'lb-dropdown-option';
      btn.dataset.value = opt.value;
      btn.textContent = opt.textContent;
      btn.addEventListener('click', () => {
        select.value = opt.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        wrap.classList.remove('is-open');
        sync();
      });
      menu.appendChild(btn);
    });

    trigger.addEventListener('click', () => wrap.classList.toggle('is-open'));
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(trigger);
    wrap.appendChild(menu);
    wrap.appendChild(select);
    sync();
  }

  window.LeadBirdDropdown = {
    init(root = document) {
      root.querySelectorAll('select[data-lb-select]').forEach(enhance);
    },
  };

  document.addEventListener('click', (e) => {
    document.querySelectorAll('.lb-dropdown.is-open').forEach((d) => {
      if (!d.contains(e.target)) d.classList.remove('is-open');
    });
  });
})();
