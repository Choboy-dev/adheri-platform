(() => {
  'use strict';

  function el(tag, attrs, children) {
    const node = document.createElement(tag);
    if (attrs) Object.entries(attrs).forEach(([k, v]) => {
      if (k === 'className') node.className = v;
      else if (k === 'text') node.textContent = v;
      else node.setAttribute(k, v);
    });
    (children || []).forEach((c) => node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c));
    return node;
  }

  window.LeadBirdModal = {
    confirm({ title = 'Confirm', message = '', confirmLabel = 'Confirm', cancelLabel = 'Cancel', danger = false } = {}) {
      return new Promise((resolve) => {
        const root = el('div', { className: 'lb-modal-root', role: 'dialog', 'aria-modal': 'true' });
        const confirmBtn = el('button', { className: 'lb-modal-confirm' + (danger ? ' is-danger' : ''), type: 'button', text: confirmLabel });
        const cancelBtn = el('button', { className: 'lb-modal-cancel', type: 'button', text: cancelLabel });
        const modal = el('div', { className: 'lb-modal' }, [
          el('h2', { text: title }),
          el('p', { text: message }),
          el('div', { className: 'lb-modal-actions' }, [cancelBtn, confirmBtn]),
        ]);
        root.appendChild(modal);
        const close = (val) => { root.remove(); resolve(val); };
        cancelBtn.addEventListener('click', () => close(false));
        confirmBtn.addEventListener('click', () => close(true));
        root.addEventListener('click', (e) => { if (e.target === root) close(false); });
        document.body.appendChild(root);
        confirmBtn.focus();
      });
    },
    alert({ title = 'Notice', message = '', confirmLabel = 'OK' } = {}) {
      return this.confirm({ title, message, confirmLabel, cancelLabel: 'Close', danger: false }).then(() => undefined);
    },
  };
})();
