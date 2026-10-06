(() => {
  'use strict';

  const body = document.body;
  const mailPath = (body.dataset.mailPath || '/mail').replace(/\/$/, '');

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const input = document.querySelector('input[name="_csrf"]');
    return input ? input.value : '';
  }

  async function postForm(url, data) {
    const fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData) && data) {
      Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    }
    if (!fd.has('_csrf')) fd.append('_csrf', csrfToken());
    const res = await fetch(url, {
      method: 'POST',
      body: fd,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken(),
      },
      credentials: 'same-origin',
    });
    return res.json().catch(() => ({ ok: false }));
  }

  function playChime() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.type = 'sine';
      o.frequency.value = 880;
      g.gain.value = 0.04;
      o.connect(g);
      g.connect(ctx.destination);
      o.start();
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.4);
      o.stop(ctx.currentTime + 0.45);
    } catch (_) { /* ignore */ }
  }

  function soundEnabled() {
    return localStorage.getItem('lb-mail-sound') !== '0';
  }

  function initSoundToggle() {
    const btn = document.querySelector('[data-mail-sound-toggle]');
    if (!btn) return;
    const sync = () => {
      btn.setAttribute('aria-pressed', soundEnabled() ? 'true' : 'false');
      btn.title = soundEnabled() ? 'Mute sound' : 'Unmute sound';
    };
    sync();
    btn.addEventListener('click', () => {
      localStorage.setItem('lb-mail-sound', soundEnabled() ? '0' : '1');
      sync();
    });
  }

  function initLiveMail() {
    if (!body.dataset.mailLatestId) return;
    let latest = Number(body.dataset.mailLatestId || 0);
    let unread = Number(body.dataset.mailUnread || 0);
    const toast = document.querySelector('[data-mail-toast]');
    const baseTitle = document.title.replace(/^\(\d+\)\s*/, '');

    const updateTitle = () => {
      document.title = unread > 0 ? `(${unread}) ${baseTitle}` : baseTitle;
    };
    updateTitle();

    const tick = async () => {
      try {
        const res = await fetch(`${mailPath}/updates`, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!data.ok) return;
        if (data.latest_id > latest) {
          latest = data.latest_id;
          body.dataset.mailLatestId = String(latest);
          if (soundEnabled()) playChime();
          if (toast) {
            toast.hidden = false;
            setTimeout(() => { toast.hidden = true; }, 3500);
          }
          const selected = document.querySelector('[data-mail-row-check]:checked');
          if (!selected) {
            const html = await fetch(location.href, { credentials: 'same-origin' }).then((r) => r.text());
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const next = doc.querySelector('[data-mail-list]');
            const cur = document.querySelector('[data-mail-list]');
            if (next && cur) {
              cur.replaceWith(next);
              initList();
            }
          }
        }
        unread = Number(data.unread || 0);
        body.dataset.mailUnread = String(unread);
        updateTitle();
      } catch (_) { /* ignore */ }
    };

    const interval = () => (document.hidden ? 60000 : 15000);
    let timer = setInterval(tick, interval());
    document.addEventListener('visibilitychange', () => {
      clearInterval(timer);
      timer = setInterval(tick, interval());
    });
  }

  function initList() {
    document.querySelectorAll('[data-mail-row]').forEach((row) => {
      row.addEventListener('click', (e) => {
        if (e.target.closest('button, input, label, a')) return;
        const href = row.getAttribute('data-href');
        if (href) location.href = href;
      });
    });

    document.querySelectorAll('[data-mail-star]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        const id = btn.getAttribute('data-mail-star');
        const data = await postForm(`${mailPath}/actions`, { action: 'star', id });
        if (data.ok) btn.classList.toggle('is-on', !!data.starred);
      });
    });

    document.querySelectorAll('[data-mail-cancel-schedule]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        const ok = window.LeadBirdModal
          ? await LeadBirdModal.confirm({ title: 'Cancel send?', message: 'This message will move to Drafts.', confirmLabel: 'Cancel send', danger: true })
          : true;
        if (!ok) return;
        const id = btn.getAttribute('data-mail-cancel-schedule');
        const data = await postForm(`${mailPath}/actions`, { action: 'cancel_schedule', id });
        if (data.ok) location.reload();
      });
    });

    const selectAll = document.querySelector('[data-mail-select-all]');
    if (selectAll) {
      selectAll.addEventListener('change', () => {
        document.querySelectorAll('[data-mail-row-check]').forEach((cb) => {
          cb.checked = selectAll.checked;
        });
      });
    }
  }

  function initRecipientReveal() {
    const btn = document.querySelector('[data-mail-more-recipients]');
    if (!btn) return;
    btn.addEventListener('click', () => {
      const extra = document.querySelector('[data-mail-extra-recipients]');
      if (extra) {
        extra.hidden = false;
        btn.hidden = true;
      }
    });
  }

  function initSidebar() {
    const sidebar = document.querySelector('[data-mail-sidebar]');
    const toggle = document.querySelector('[data-mail-sidebar-toggle]');
    const backdrop = document.querySelector('[data-mail-sidebar-backdrop]');
    if (!sidebar || !toggle) return;
    const close = () => {
      sidebar.classList.remove('is-open');
      if (backdrop) backdrop.hidden = true;
    };
    toggle.addEventListener('click', () => {
      const open = sidebar.classList.toggle('is-open');
      if (backdrop) backdrop.hidden = !open;
    });
    if (backdrop) backdrop.addEventListener('click', close);
  }

  function initFrames() {
    document.querySelectorAll('.mail-body-frame').forEach((frame) => {
      const resize = () => {
        try {
          const doc = frame.contentDocument;
          if (!doc) return;
          frame.style.height = Math.max(280, doc.documentElement.scrollHeight + 16) + 'px';
        } catch (_) { /* sandbox */ }
      };
      frame.addEventListener('load', resize);
      setTimeout(resize, 250);
    });
  }

  function initPopovers() {
    document.querySelectorAll('[data-mail-pop-toggle]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const key = btn.getAttribute('data-mail-pop-toggle');
        document.querySelectorAll('[data-mail-pop]').forEach((p) => {
          const match = p.getAttribute('data-mail-pop') === key;
          p.hidden = match ? !p.hidden : true;
        });
      });
    });
    document.addEventListener('click', (e) => {
      if (e.target.closest('[data-mail-pop], [data-mail-pop-toggle]')) return;
      document.querySelectorAll('[data-mail-pop]').forEach((p) => { p.hidden = true; });
    });
  }

  function formatBytes(bytes) {
    if (bytes >= 1048576) return (Math.round(bytes / 104857.6) / 10) + ' MB';
    if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
    return bytes + ' B';
  }

  function compressImageFile(file, maxEdge = 1600, quality = 0.75) {
    if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml') {
      return Promise.resolve(file);
    }
    // Shared hosting often caps uploads at 2MB; shrink anything above ~700KB.
    if (file.size <= 700 * 1024) {
      return Promise.resolve(file);
    }
    return new Promise((resolve) => {
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => {
        URL.revokeObjectURL(url);
        let { width, height } = img;
        const scale = Math.min(1, maxEdge / Math.max(width, height));
        width = Math.max(1, Math.round(width * scale));
        height = Math.max(1, Math.round(height * scale));
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
          resolve(file);
          return;
        }
        ctx.drawImage(img, 0, 0, width, height);
        const tryQuality = async (q) => new Promise((res) => {
          canvas.toBlob((blob) => res(blob), 'image/jpeg', q);
        });
        (async () => {
          let blob = await tryQuality(quality);
          if (blob && blob.size > 1.6 * 1024 * 1024) {
            blob = await tryQuality(0.65);
          }
          if (blob && blob.size > 1.6 * 1024 * 1024) {
            // Second pass at smaller edge
            const c2 = document.createElement('canvas');
            const s2 = Math.min(1, 1280 / Math.max(width, height));
            c2.width = Math.max(1, Math.round(width * s2));
            c2.height = Math.max(1, Math.round(height * s2));
            const ctx2 = c2.getContext('2d');
            if (ctx2) {
              ctx2.drawImage(canvas, 0, 0, c2.width, c2.height);
              blob = await new Promise((res) => c2.toBlob((b) => res(b), 'image/jpeg', 0.62));
            }
          }
          if (!blob || blob.size >= file.size) {
            resolve(file);
            return;
          }
          const name = file.name.replace(/\.\w+$/, '') + '.jpg';
          resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
        })();
      };
      img.onerror = () => {
        URL.revokeObjectURL(url);
        resolve(file);
      };
      img.src = url;
    });
  }

  function createAttachments(input, listEl, opts = {}) {
    let files = [];
    const maxBytes = Number(opts.maxBytes) || 10 * 1024 * 1024;
    const maxTotal = Number(opts.maxTotal) || maxBytes;
    const maxCount = Number(opts.maxCount) || 10;
    const sync = () => {
      const dt = new DataTransfer();
      files.forEach((f) => dt.items.add(f));
      input.files = dt.files;
      listEl.innerHTML = '';
      files.forEach((f, i) => {
        const chip = document.createElement('span');
        chip.className = 'compose-attach-chip';
        chip.innerHTML = `<span class="compose-attach-name"></span><small class="compose-attach-size"></small> `;
        chip.querySelector('.compose-attach-name').textContent = f.name;
        chip.querySelector('.compose-attach-size').textContent = formatBytes(f.size);
        const x = document.createElement('button');
        x.type = 'button';
        x.setAttribute('aria-label', 'Remove ' + f.name);
        x.textContent = '×';
        x.addEventListener('click', () => {
          files = files.filter((_, idx) => idx !== i);
          sync();
        });
        chip.appendChild(x);
        listEl.appendChild(chip);
      });
    };
    input.addEventListener('change', async () => {
      const incoming = Array.from(input.files || []);
      const rejected = [];
      for (const original of incoming) {
        if (files.some((x) => x.name === original.name && x.size === original.size && x.lastModified === original.lastModified)) {
          continue;
        }
        if (files.length >= maxCount) {
          rejected.push(`${original.name}: max ${maxCount} files`);
          continue;
        }
        let f = await compressImageFile(original);
        if (f.size > maxBytes) {
          rejected.push(`${original.name}: still ${formatBytes(f.size)} after compress (limit ${formatBytes(maxBytes)})`);
          continue;
        }
        if (files.reduce((sum, x) => sum + x.size, 0) + f.size > maxTotal) {
          rejected.push(`${original.name}: would exceed ${formatBytes(maxTotal)} total`);
          continue;
        }
        files.push(f);
      }
      sync();
      if (rejected.length && window.LeadBirdModal) {
        LeadBirdModal.alert({
          title: 'Attachment not added',
          message: rejected.join('\n'),
        });
      }
    });
    return {
      clear: () => { files = []; sync(); },
      list: () => files.slice(),
      totalBytes: () => files.reduce((sum, f) => sum + f.size, 0),
    };
  }

  function initCompose() {
    const panel = document.querySelector('[data-mail-compose]');
    if (!panel) return;
    const form = panel.querySelector('[data-mail-compose-form]');
    const editor = panel.querySelector('[data-mail-editor]');
    const htmlField = panel.querySelector('[data-mail-html-field]');
    const actionField = panel.querySelector('[data-mail-compose-action]');
    const toolbar = panel.querySelector('[data-mail-toolbar]');
    const attachInput = panel.querySelector('[data-mail-attach-input]');
    const attachList = panel.querySelector('[data-mail-attach-list]');
    const scheduledAt = panel.querySelector('[data-mail-scheduled-at]');
    const scheduleSheet = panel.querySelector('[data-mail-schedule]');
    const sendBtn = panel.querySelector('[data-mail-send]');
    const sendLabel = panel.querySelector('[data-mail-send-label]');
    const sendSpinner = panel.querySelector('[data-mail-send-spinner]');
    const sendStatus = panel.querySelector('[data-mail-send-status]');
    const sendingOverlay = panel.querySelector('[data-mail-sending-overlay]');
    const sendingTitle = panel.querySelector('[data-mail-sending-title]');
    const sendingCopy = panel.querySelector('[data-mail-sending-copy]');
    const maxAttach = Number(form?.getAttribute('data-mail-max-attach') || 10485760);
    const maxAttachTotal = Number(form?.getAttribute('data-mail-max-attach-total') || maxAttach);
    const maxAttachCount = Number(form?.getAttribute('data-mail-max-attach-count') || 10);

    const attachments = (attachInput && attachList)
      ? createAttachments(attachInput, attachList, {
        maxBytes: maxAttach,
        maxTotal: maxAttachTotal,
        maxCount: maxAttachCount,
      })
      : null;

    let submitting = false;
    let sendAbort = null;

    const syncHtml = () => {
      if (htmlField && editor) htmlField.value = editor.innerHTML;
    };

    const setSendingUi = (on, mode = 'send') => {
      submitting = on;
      panel.classList.toggle('is-sending', on);
      if (sendingOverlay) sendingOverlay.hidden = !on;
      if (sendBtn) sendBtn.disabled = on;
      panel.querySelector('[data-mail-schedule-open]')?.toggleAttribute('disabled', on);
      if (sendSpinner) sendSpinner.hidden = !on;
      if (sendStatus) {
        sendStatus.hidden = !on;
        sendStatus.textContent = mode === 'draft' ? 'Saving…' : (mode === 'schedule' ? 'Scheduling…' : 'Sending…');
      }
      if (sendLabel) {
        sendLabel.textContent = on
          ? (mode === 'draft' ? 'Saving…' : (mode === 'schedule' ? 'Scheduling…' : 'Sending…'))
          : 'Send';
      }
      if (sendingTitle) {
        sendingTitle.textContent = mode === 'draft'
          ? 'Saving draft…'
          : (mode === 'schedule' ? 'Scheduling message…' : 'Sending message…');
      }
      if (sendingCopy) {
        const n = attachments?.list()?.length || 0;
        sendingCopy.textContent = n > 0
          ? `Uploading ${n} attachment${n === 1 ? '' : 's'}. Please wait.`
          : 'Please wait a moment.';
      }
    };

    const showComposeError = (message) => {
      if (window.LeadBirdModal) {
        LeadBirdModal.alert({ title: 'Could not send', message });
      } else {
        window.alert(message);
      }
    };

    const submitCompose = async (modeHint) => {
      if (!form || submitting) return;
      syncHtml();
      const action = actionField?.value || 'send';
      const total = attachments?.totalBytes() || 0;
      if (total > maxAttachTotal) {
        showComposeError(`Attachments are ${formatBytes(total)}. Limit is ${formatBytes(maxAttachTotal)}. Compress or remove some files.`);
        return;
      }
      const oversized = (attachments?.list() || []).filter((f) => f.size > maxAttach);
      if (oversized.length) {
        showComposeError(`"${oversized[0].name}" is ${formatBytes(oversized[0].size)}. Max per file is ${formatBytes(maxAttach)}.`);
        return;
      }

      const mode = modeHint || (action === 'draft' ? 'draft' : ((scheduledAt?.value || '') ? 'schedule' : 'send'));
      setSendingUi(true, mode);
      sendAbort = new AbortController();
      const timeoutId = window.setTimeout(() => sendAbort?.abort(), 120000);

        try {
          const files = attachments?.list() || [];
          const staged = [];
          for (let i = 0; i < files.length; i++) {
            if (sendingCopy) {
              sendingCopy.textContent = `Uploading attachment ${i + 1} of ${files.length}…`;
            }
            const up = new FormData();
            up.append('file', files[i], files[i].name);
            up.append('_csrf', csrfToken());
            const upRes = await fetch(`${mailPath}/upload`, {
              method: 'POST',
              body: up,
              credentials: 'same-origin',
              signal: sendAbort.signal,
              headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken(),
              },
            });
            const upRaw = await upRes.text();
            let upData = null;
            try { upData = JSON.parse(upRaw); } catch (_) { upData = null; }
            if (!upData || upData.ok === false) {
              setSendingUi(false);
              const snippet = (upData && upData.message)
                || upRaw.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180)
                || `Upload failed (HTTP ${upRes.status}).`;
              showComposeError(snippet);
              return;
            }
            if (upData.token) staged.push(String(upData.token));
          }

          if (sendingCopy) {
            sendingCopy.textContent = staged.length
              ? 'Sending message…'
              : 'Please wait a moment.';
          }

          const payload = {
            _csrf: csrfToken(),
            action: actionField?.value || 'send',
            to: form.querySelector('[name="to"]')?.value || '',
            cc: form.querySelector('[name="cc"]')?.value || '',
            bcc: form.querySelector('[name="bcc"]')?.value || '',
            subject: form.querySelector('[name="subject"]')?.value || '',
            html: htmlField?.value || editor?.innerHTML || '',
            draft_id: form.querySelector('[name="draft_id"]')?.value || '',
            in_reply_to: form.querySelector('[name="in_reply_to"]')?.value || '',
            scheduled_at: scheduledAt?.value || '',
            return_to: form.querySelector('[name="return_to"]')?.value || mailPath,
            staged,
          };

          const res = await fetch(`${mailPath}/send`, {
            method: 'POST',
            body: JSON.stringify(payload),
            credentials: 'same-origin',
            signal: sendAbort.signal,
            headers: {
              Accept: 'application/json',
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': csrfToken(),
            },
          });
          const raw = await res.text();
          let data = null;
          try { data = JSON.parse(raw); } catch (_) { data = null; }
          if (!data) {
            setSendingUi(false);
            const snippet = raw.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180);
            showComposeError(
              snippet
                ? `Server error (HTTP ${res.status}): ${snippet}`
                : `Send failed (HTTP ${res.status}). Hard-refresh the page (Cmd+Shift+R) and try again.`
            );
            return;
          }
          if (!res.ok || data.ok === false) {
            setSendingUi(false);
            showComposeError(data.message || `Send failed (HTTP ${res.status}).`);
            return;
          }
          window.location.href = data.redirect || `${mailPath}?folder=sent`;
        } catch (err) {
          setSendingUi(false);
          const aborted = err && (err.name === 'AbortError' || sendAbort?.signal.aborted);
          showComposeError(
            aborted
              ? 'Sending timed out. Try smaller attachments (large photos often hang on shared hosting).'
              : 'Network error while sending. Check your connection and try again.'
          );
        } finally {
          window.clearTimeout(timeoutId);
          sendAbort = null;
        }
      };

    form?.addEventListener('submit', (e) => {
      e.preventDefault();
      submitCompose();
    });

    panel.querySelector('[data-mail-compose-min]')?.addEventListener('click', () => {
      panel.classList.toggle('is-min');
      panel.classList.remove('is-full');
    });
    panel.querySelector('[data-mail-compose-full]')?.addEventListener('click', () => {
      panel.classList.toggle('is-full');
      panel.classList.remove('is-min');
    });
    panel.querySelector('[data-mail-compose-close]')?.addEventListener('click', async () => {
      if (submitting) return;
      syncHtml();
      if (actionField) actionField.value = 'draft';
      submitCompose('draft');
    });

    panel.querySelector('[data-mail-toggle-format]')?.addEventListener('click', () => {
      if (!toolbar) return;
      const open = toolbar.hidden;
      toolbar.hidden = !open;
      const toggle = panel.querySelector('[data-mail-toggle-format]');
      toggle?.classList.toggle('is-active', open);
      toggle?.setAttribute('aria-pressed', open ? 'true' : 'false');
      if (open) editor?.focus();
    });
    panel.querySelector('[data-mail-attach-btn]')?.addEventListener('click', () => {
      if (submitting) return;
      attachInput?.click();
    });
    panel.querySelector('[data-mail-save-draft]')?.addEventListener('click', () => {
      if (submitting) return;
      syncHtml();
      if (actionField) actionField.value = 'draft';
      submitCompose('draft');
    });
    panel.querySelector('[data-mail-discard]')?.addEventListener('click', async () => {
      if (submitting) return;
      const ok = window.LeadBirdModal
        ? await LeadBirdModal.confirm({ title: 'Discard draft?', message: 'This cannot be undone.', confirmLabel: 'Discard', danger: true })
        : true;
      if (!ok) return;
      const draftId = form?.querySelector('[name="draft_id"]')?.value;
      if (draftId) {
        await postForm(`${mailPath}/actions`, { action: 'discard_draft', id: draftId });
      }
      location.href = mailPath;
    });

    const recipientsRoot = panel.querySelector('[data-mail-recipients]');
    const fields = panel.querySelector('[data-mail-recip-fields]');
    const summary = panel.querySelector('[data-mail-recip-summary]');
    const toInput = form?.querySelector('[name="to"]');
    const ccInput = form?.querySelector('[name="cc"]');
    const bccInput = form?.querySelector('[name="bcc"]');
    const ccRow = panel.querySelector('[data-mail-cc-row]');
    const bccRow = panel.querySelector('[data-mail-bcc-row]');
    const ccToggle = panel.querySelector('[data-mail-toggle-cc]');
    const bccToggle = panel.querySelector('[data-mail-toggle-bcc]');

    const expandRecipients = () => {
      if (summary) summary.hidden = true;
      if (fields) fields.hidden = false;
    };
    const recipientLabel = (raw) => {
      const part = String(raw || '').trim();
      if (!part) return '';
      const named = part.match(/^(.+?)\s*<([^>]+)>$/);
      if (named) {
        const name = named[1].trim().replace(/^["']|["']$/g, '');
        const first = name.split(/\s+/).filter(Boolean)[0];
        if (first) return first;
        return named[2].split('@')[0].split(/[._+-]/)[0] || named[2];
      }
      if (part.includes('@')) {
        const local = part.split('@')[0];
        return local.split(/[._+-]/)[0] || local;
      }
      return part.split(/\s+/).filter(Boolean)[0] || part;
    };
    const parseRecipientParts = (raw) =>
      String(raw || '')
        .split(/[,;]+/)
        .map((s) => s.trim())
        .filter(Boolean);
    const formatRecipientSummary = (toRaw, ccRaw, bccRaw) => {
      const labels = [...parseRecipientParts(toRaw), ...parseRecipientParts(ccRaw)]
        .map(recipientLabel)
        .filter(Boolean);
      const bccCount = parseRecipientParts(bccRaw).length;
      if (labels.length === 0) return '';

      const maxChars = 36;
      let visibleCount = 1;
      if (labels.length >= 2) {
        const two = `${labels[0]}, ${labels[1]}`;
        visibleCount = two.length <= maxChars ? 2 : 1;
      }
      const visible = labels.slice(0, visibleCount);
      const remaining = labels.length - visible.length;
      let text = visible.join(', ');
      if (remaining > 0) text += ` +${remaining} more`;
      if (bccCount > 0) text += ` (${bccCount} Bcc)`;
      return text;
    };
    const collapseRecipients = () => {
      if (!fields || !summary || !toInput) return;
      const to = toInput.value.trim();
      if (!to) return;
      const cc = (ccInput?.value || '').trim();
      const bcc = (bccInput?.value || '').trim();
      summary.textContent = formatRecipientSummary(to, cc, bcc);
      summary.title = [to, cc, bcc].filter(Boolean).join('\n');
      summary.hidden = false;
      fields.hidden = true;
    };
    const showExtraRow = (row, focusInput, toggleBtn) => {
      expandRecipients();
      if (!row) return;
      row.hidden = false;
      if (toggleBtn) toggleBtn.hidden = true;
      focusInput?.focus();
    };

    // mousedown so To blur does not collapse before Cc/Bcc runs (Gmail pattern)
    ccToggle?.addEventListener('mousedown', (e) => {
      e.preventDefault();
      e.stopPropagation();
      showExtraRow(ccRow, ccInput, ccToggle);
    });
    bccToggle?.addEventListener('mousedown', (e) => {
      e.preventDefault();
      e.stopPropagation();
      showExtraRow(bccRow, bccInput, bccToggle);
    });

    const maybeCollapse = (related) => {
      if (related && recipientsRoot?.contains(related)) return;
      window.setTimeout(() => {
        if (recipientsRoot?.contains(document.activeElement)) return;
        collapseRecipients();
      }, 0);
    };

    toInput?.addEventListener('blur', (e) => maybeCollapse(e.relatedTarget));
    ccInput?.addEventListener('blur', (e) => maybeCollapse(e.relatedTarget));
    bccInput?.addEventListener('blur', (e) => maybeCollapse(e.relatedTarget));
    summary?.addEventListener('click', () => {
      expandRecipients();
      toInput?.focus();
    });

    if ((ccInput?.value || '').trim() && ccRow) {
      ccRow.hidden = false;
      if (ccToggle) ccToggle.hidden = true;
    }
    if ((bccInput?.value || '').trim() && bccRow) {
      bccRow.hidden = false;
      if (bccToggle) bccToggle.hidden = true;
    }

    // Toolbar — Gmail strip + overflow menus
    toolbar?.querySelectorAll('[data-cmd]').forEach((ctrl) => {
      const cmd = ctrl.getAttribute('data-cmd');
      const run = () => {
        editor?.focus();
        if (cmd === 'fontName' || cmd === 'fontSize' || cmd === 'foreColor' || cmd === 'hiliteColor') {
          document.execCommand(cmd, false, ctrl.value);
        } else if (cmd === 'formatBlock') {
          document.execCommand(cmd, false, ctrl.getAttribute('data-value') || 'blockquote');
        } else if (cmd === 'insertImage') {
          const input = document.createElement('input');
          input.type = 'file';
          input.accept = 'image/*';
          input.onchange = async () => {
            const file = input.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('image', file);
            fd.append('_csrf', csrfToken());
            const res = await fetch(`${mailPath}/upload-image`, { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await res.json();
            if (data.ok && data.url) {
              document.execCommand('insertImage', false, data.url);
            } else if (window.LeadBirdModal) {
              LeadBirdModal.alert({ title: 'Upload failed', message: data.error || 'Could not upload image' });
            }
          };
          input.click();
        } else {
          document.execCommand(cmd, false, null);
        }
        // Close align / more format pops after applying
        if (cmd && ['justifyLeft', 'justifyCenter', 'justifyRight', 'strikeThrough', 'formatBlock', 'unlink', 'removeFormat', 'insertImage'].includes(cmd)) {
          panel.querySelectorAll('[data-mail-pop="align"], [data-mail-pop="format-more"]').forEach((p) => { p.hidden = true; });
        }
      };
      if (ctrl.tagName === 'SELECT' || ctrl.type === 'color') {
        ctrl.addEventListener('change', run);
      } else {
        ctrl.addEventListener('click', (e) => { e.preventDefault(); run(); });
      }
    });

    // Sync color underline swatch with picker
    const colorInput = toolbar?.querySelector('[data-cmd="foreColor"]');
    const colorSwatch = toolbar?.querySelector('.gmail-color-a');
    colorInput?.addEventListener('input', () => {
      if (colorSwatch) colorSwatch.style.borderBottomColor = colorInput.value;
    });

    panel.querySelector('[data-mail-link-apply]')?.addEventListener('click', () => {
      const url = panel.querySelector('[data-mail-link-url]')?.value;
      if (url) {
        editor?.focus();
        document.execCommand('createLink', false, url);
      }
      const linkPop = panel.querySelector('[data-mail-pop="link"]');
      if (linkPop) linkPop.hidden = true;
    });

    panel.querySelectorAll('[data-mail-insert-sig]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const html = btn.getAttribute('data-sig-html') || '';
        if (editor) editor.innerHTML += `<div class="lb-mail-signature">${html}</div>`;
        const sigPop = panel.querySelector('[data-mail-pop="sig"]');
        if (sigPop) sigPop.hidden = true;
      });
    });

    // Schedule
    const openSchedule = () => {
      if (!scheduleSheet) return;
      scheduleSheet.hidden = false;
      requestAnimationFrame(() => scheduleSheet.classList.add('is-open'));
    };
    const closeSchedule = () => {
      if (!scheduleSheet) return;
      scheduleSheet.classList.remove('is-open');
      window.setTimeout(() => { scheduleSheet.hidden = true; }, 220);
    };
    const preview = panel.querySelector('[data-schedule-preview]');
    const customHidden = panel.querySelector('[data-schedule-custom]');
    const dateInput = panel.querySelector('[data-schedule-date]');
    const timeInput = panel.querySelector('[data-schedule-time]');

    const setPreview = (label) => {
      if (preview) preview.textContent = label ? `Sends ${label}` : 'Choose a time';
    };
    const syncCustomFromValue = (value) => {
      if (!value || !dateInput || !timeInput) return;
      const [d, t] = value.replace('T', ' ').split(' ');
      if (d) dateInput.value = d;
      if (t) timeInput.value = t.slice(0, 5);
      if (customHidden) customHidden.value = `${d} ${t.slice(0, 5)}`;
    };
    const selectChoice = (btn) => {
      panel.querySelectorAll('[data-schedule-value]').forEach((el) => {
        el.classList.toggle('is-selected', el === btn);
        el.setAttribute('aria-selected', el === btn ? 'true' : 'false');
      });
      const value = btn.getAttribute('data-schedule-value') || '';
      syncCustomFromValue(value);
      setPreview(btn.getAttribute('data-schedule-label') || '');
    };
    const applyCustomFields = () => {
      if (!dateInput || !timeInput || !customHidden) return;
      if (!dateInput.value || !timeInput.value) return;
      const value = `${dateInput.value} ${timeInput.value}`;
      customHidden.value = value;
      panel.querySelectorAll('[data-schedule-value]').forEach((el) => {
        el.classList.remove('is-selected');
        el.setAttribute('aria-selected', 'false');
      });
      const d = new Date(`${dateInput.value}T${timeInput.value}`);
      const label = Number.isNaN(d.getTime())
        ? value
        : d.toLocaleString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false });
      setPreview(label.replace(',', ' ·'));
    };

    panel.querySelector('[data-mail-schedule-open]')?.addEventListener('click', () => {
      const first = panel.querySelector('.mail-schedule-choice');
      if (first) selectChoice(first);
      openSchedule();
    });
    panel.querySelectorAll('[data-schedule-cancel]').forEach((btn) => {
      btn.addEventListener('click', closeSchedule);
    });
    panel.querySelectorAll('.mail-schedule-choice').forEach((btn) => {
      btn.addEventListener('click', () => selectChoice(btn));
    });
    dateInput?.addEventListener('change', applyCustomFields);
    timeInput?.addEventListener('change', applyCustomFields);
    timeInput?.addEventListener('input', applyCustomFields);
    panel.querySelector('[data-schedule-confirm]')?.addEventListener('click', () => {
      let val = customHidden?.value || '';
      if (val.includes('T')) val = val.replace('T', ' ');
      if (!val.trim()) {
        if (window.LeadBirdModal) {
          LeadBirdModal.alert({ title: 'Pick a time', message: 'Choose a suggested time or set a custom date and time.' });
        }
        return;
      }
      if (scheduledAt) scheduledAt.value = val;
      closeSchedule();
      syncHtml();
      if (actionField) actionField.value = 'send';
      submitCompose('schedule');
    });

    // Paste images
    editor?.addEventListener('paste', async (e) => {
      const items = e.clipboardData?.items;
      if (!items) return;
      for (const item of items) {
        if (item.type.startsWith('image/')) {
          e.preventDefault();
          const file = item.getAsFile();
          if (!file) return;
          const fd = new FormData();
          fd.append('image', file);
          fd.append('_csrf', csrfToken());
          const res = await fetch(`${mailPath}/upload-image`, { method: 'POST', body: fd, credentials: 'same-origin' });
          const data = await res.json();
          if (data.ok && data.url) document.execCommand('insertImage', false, data.url);
        }
      }
    });
  }

  function initSignatureForms() {
    document.querySelectorAll('[data-mail-sig-delete]').forEach((form) => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const ok = window.LeadBirdModal
          ? await LeadBirdModal.confirm({ title: 'Delete signature?', message: 'This cannot be undone.', confirmLabel: 'Delete', danger: true })
          : true;
        if (ok) form.submit();
      });
    });
  }

  function initAccountMenu() {
    const root = document.querySelector('[data-mail-account]');
    if (!root) return;
    const toggle = root.querySelector('[data-mail-account-toggle]');
    const menu = root.querySelector('[data-mail-account-menu]');
    if (!toggle || !menu) return;

    const close = () => {
      menu.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
    };
    const open = () => {
      menu.hidden = false;
      toggle.setAttribute('aria-expanded', 'true');
    };

    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      if (menu.hidden) open();
      else close();
    });
    document.addEventListener('click', (e) => {
      if (!root.contains(e.target)) close();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
    });
  }

  function initAvatarForm() {
    const input = document.querySelector('[data-mail-avatar-input]');
    const nameEl = document.querySelector('[data-mail-avatar-name]');
    if (!input) return;
    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if (file && file.size > 3 * 1024 * 1024) {
        if (window.LeadBirdModal) LeadBirdModal.alert({ title: 'Too large', message: 'Max 3 MB.' });
        input.value = '';
        if (nameEl) nameEl.textContent = 'No file chosen';
        return;
      }
      if (nameEl) nameEl.textContent = file ? file.name : 'No file chosen';
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initList();
    initSoundToggle();
    initLiveMail();
    initRecipientReveal();
    initSidebar();
    initFrames();
    initPopovers();
    initCompose();
    initSignatureForms();
    initAvatarForm();
    initAccountMenu();
    if (window.LeadBirdDropdown) LeadBirdDropdown.init();
  });
})();
