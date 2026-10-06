<?php

declare(strict_types=1);

/** @var array<string, mixed> $compose */
/** @var list<array<string, mixed>> $signatures */
/** @var list<array{label:string,value:string,day:string,time:string,period:string}> $scheduleOptions */
/** @var Adheri\Mail\MailService $mail */

$open = !empty($compose['open']);
$ccVal = trim((string) ($compose['cc'] ?? ''));
$bccVal = trim((string) ($compose['bcc'] ?? ''));
?>
<aside class="compose-panel<?= $open ? ' is-open' : '' ?>" data-mail-compose<?= $open ? '' : ' hidden' ?>>
  <header class="compose-chrome">
    <strong data-mail-compose-title><?= mail_h((string) ($compose['title'] ?? 'New message')) ?></strong>
    <div class="compose-chrome-actions">
      <button type="button" data-mail-compose-min aria-label="Minimize">
        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M6 12h12v2H6z"/></svg>
      </button>
      <button type="button" data-mail-compose-full aria-label="Full screen">
        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
      </button>
      <button type="button" data-mail-compose-close aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
  </header>
  <form
    class="compose-form"
    method="post"
    action="<?= mail_h(mail_path('compose')) ?>"
    enctype="multipart/form-data"
    data-mail-compose-form
    <?php $attachLimits = $mail->uploadLimits(); ?>
    data-mail-max-attach="<?= (int) $attachLimits['per_file'] ?>"
    data-mail-max-attach-total="<?= (int) $attachLimits['total'] ?>"
    data-mail-max-attach-count="<?= (int) \Adheri\Config::int('MAIL_MAX_ATTACHMENTS', 10) ?>"
  >
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="send" data-mail-compose-action>
    <input type="hidden" name="draft_id" value="<?= mail_h((string) ($compose['draft_id'] ?? '')) ?>">
    <input type="hidden" name="in_reply_to" value="<?= mail_h((string) ($compose['in_reply_to'] ?? '')) ?>">
    <input type="hidden" name="scheduled_at" value="" data-mail-scheduled-at>
    <input type="hidden" name="return_to" value="<?= mail_h(mail_path()) ?>">
    <input type="hidden" name="html" value="" data-mail-html-field>

    <div class="compose-recipients" data-mail-recipients>
      <button type="button" class="compose-recip-summary" data-mail-recip-summary hidden></button>
      <div class="compose-recip-fields" data-mail-recip-fields>
        <div class="compose-row compose-row-to">
          <span class="compose-row-label">To</span>
          <input type="text" name="to" value="<?= mail_h((string) ($compose['to'] ?? '')) ?>" autocomplete="off" data-mail-to aria-label="To">
          <div class="compose-recip-toggles">
            <button type="button" class="compose-cc-toggle" data-mail-toggle-cc<?= $ccVal !== '' ? ' hidden' : '' ?>>Cc</button>
            <button type="button" class="compose-bcc-toggle" data-mail-toggle-bcc<?= $bccVal !== '' ? ' hidden' : '' ?>>Bcc</button>
          </div>
        </div>
        <div class="compose-row" data-mail-cc-row<?= $ccVal === '' ? ' hidden' : '' ?>>
          <span class="compose-row-label">Cc</span>
          <input type="text" name="cc" value="<?= mail_h($ccVal) ?>" autocomplete="off" data-mail-cc aria-label="Cc">
        </div>
        <div class="compose-row" data-mail-bcc-row<?= $bccVal === '' ? ' hidden' : '' ?>>
          <span class="compose-row-label">Bcc</span>
          <input type="text" name="bcc" value="<?= mail_h($bccVal) ?>" autocomplete="off" data-mail-bcc aria-label="Bcc">
        </div>
      </div>
    </div>

    <div class="compose-row compose-subject">
      <input type="text" name="subject" value="<?= mail_h((string) ($compose['subject'] ?? '')) ?>" placeholder="Subject" aria-label="Subject">
    </div>

    <div class="compose-editor" contenteditable="true" role="textbox" aria-multiline="true" data-mail-editor><?= $compose['html'] ?? '' ?></div>

    <div class="compose-attach-list" data-mail-attach-list></div>
    <input type="file" name="attachments[]" multiple hidden data-mail-attach-input accept=".pdf,.txt,.csv,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip">

    <?php require __DIR__ . '/editor-toolbar.php'; ?>

    <footer class="compose-footer gmail-compose-footer">
      <div class="compose-send-split">
        <button type="submit" class="btn-mail-primary" data-mail-send>
          <span class="compose-send-label" data-mail-send-label>Send</span>
          <span class="compose-send-spinner" data-mail-send-spinner hidden aria-hidden="true"></span>
        </button>
        <button type="button" class="btn-mail-caret" data-mail-schedule-open aria-label="More send options" title="Schedule send">
          <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
        </button>
      </div>
      <p class="compose-send-status" data-mail-send-status hidden>Sending…</p>

      <button type="button" class="gmail-tool-btn gmail-format-toggle" data-mail-toggle-format title="Formatting options" aria-label="Formatting options" aria-pressed="false">
        <span class="gmail-format-toggle-off" aria-hidden="true">A</span>
        <span class="gmail-format-toggle-on" aria-hidden="true">Aa</span>
      </button>

      <button type="button" class="gmail-tool-btn" data-mail-attach-btn title="Attach files" aria-label="Attach files">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg>
      </button>

      <div class="mail-pop-wrap">
        <button type="button" class="gmail-tool-btn" data-mail-pop-toggle="link" title="Insert link" aria-label="Insert link">
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
        </button>
        <div class="mail-pop" data-mail-pop="link" hidden>
          <input type="url" placeholder="https://" data-mail-link-url aria-label="Link URL">
          <button type="button" data-mail-link-apply>Apply</button>
        </div>
      </div>

      <div class="mail-pop-wrap">
        <button type="button" class="gmail-tool-btn" data-mail-pop-toggle="sig" title="Insert signature" aria-label="Insert signature">
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 0 0 0-1.41l-2.34-2.34a1.003 1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        </button>
        <div class="mail-pop" data-mail-pop="sig" hidden>
          <?php foreach ($signatures as $sig): ?>
            <button type="button" data-mail-insert-sig="<?= (int) $sig['id'] ?>" data-sig-html="<?= mail_h((string) $sig['body_html']) ?>"><?= mail_h((string) $sig['name']) ?></button>
          <?php endforeach; ?>
          <?php if ($signatures === []): ?>
            <p class="mail-muted">No signatures yet</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="mail-pop-wrap">
        <button type="button" class="gmail-tool-btn" data-mail-pop-toggle="more" title="More options" aria-label="More options">
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
        </button>
        <div class="mail-pop" data-mail-pop="more" hidden>
          <button type="button" data-mail-save-draft>Save draft</button>
        </div>
      </div>

      <button type="button" class="gmail-tool-btn gmail-discard" data-mail-discard title="Discard draft" aria-label="Discard draft">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
      </button>
    </footer>
  </form>

  <div class="mail-schedule" data-mail-schedule hidden>
    <button type="button" class="mail-schedule-scrim" data-schedule-cancel aria-label="Close schedule"></button>
    <div class="mail-schedule-panel" role="dialog" aria-modal="true" aria-labelledby="mail-schedule-title">
      <header class="mail-schedule-head">
        <div>
          <h3 id="mail-schedule-title">Send later</h3>
          <p class="mail-schedule-tz">West Africa Time · Lagos</p>
        </div>
        <button type="button" class="mail-schedule-close" data-schedule-cancel aria-label="Close">×</button>
      </header>

      <div class="mail-schedule-choices" role="listbox" aria-label="Suggested times">
        <?php foreach ($scheduleOptions as $i => $opt): ?>
          <button
            type="button"
            class="mail-schedule-choice<?= $i === 0 ? ' is-selected' : '' ?>"
            role="option"
            aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
            data-schedule-value="<?= mail_h($opt['value']) ?>"
            data-schedule-label="<?= mail_h($opt['day'] . ' · ' . $opt['time']) ?>"
          >
            <span class="mail-schedule-choice-copy">
              <span class="mail-schedule-period"><?= mail_h($opt['period']) ?></span>
              <span class="mail-schedule-day"><?= mail_h($opt['day']) ?></span>
            </span>
            <span class="mail-schedule-clock"><?= mail_h($opt['time']) ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="mail-schedule-custom-shell">
        <div class="mail-schedule-custom-core">
          <p class="mail-schedule-custom-label">Or pick a time</p>
          <div class="mail-schedule-custom-fields">
            <label class="mail-schedule-field">
              <span>Date</span>
              <input type="date" data-schedule-date>
            </label>
            <label class="mail-schedule-field">
              <span>Time</span>
              <input type="time" data-schedule-time step="60">
            </label>
          </div>
          <input type="hidden" data-schedule-custom value="<?= mail_h($scheduleOptions[0]['value'] ?? '') ?>">
        </div>
      </div>

      <p class="mail-schedule-preview" data-schedule-preview>
        Sends <?= mail_h(($scheduleOptions[0]['day'] ?? '') . ' · ' . ($scheduleOptions[0]['time'] ?? '')) ?>
      </p>

      <footer class="mail-schedule-actions">
        <button type="button" class="mail-schedule-secondary" data-schedule-cancel>Cancel</button>
        <button type="button" class="mail-schedule-primary" data-schedule-confirm>
          Schedule
          <span class="mail-schedule-primary-icon" aria-hidden="true">↗</span>
        </button>
      </footer>
    </div>
  </div>
  <div class="compose-sending-overlay" data-mail-sending-overlay hidden>
    <div class="compose-sending-card" role="status" aria-live="polite">
      <span class="compose-send-spinner is-visible" aria-hidden="true"></span>
      <strong data-mail-sending-title>Sending message…</strong>
      <p data-mail-sending-copy>Please wait. Large attachments can take a moment.</p>
    </div>
  </div>
</aside>
