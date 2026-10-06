<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var array<string, string|null> $settings */

$v = static fn (string $key): string => (string) ($settings[$key] ?? '');

$sections = [
    [
        'id' => 'identity',
        'title' => 'Identity',
        'blurb' => 'How ADHERI is named across the public site.',
        'fields' => [
            ['key' => 'org_name', 'label' => 'Organisation name', 'type' => 'text', 'hint' => 'Full legal / public name'],
            ['key' => 'org_short_name', 'label' => 'Short name', 'type' => 'text', 'hint' => 'Used in nav, footer, and titles'],
        ],
    ],
    [
        'id' => 'contact',
        'title' => 'Contact',
        'blurb' => 'Emails and phones shown on Contact and in inquiry mail.',
        'fields' => [
            ['key' => 'contact_email_primary', 'label' => 'Primary email', 'type' => 'email', 'hint' => 'Main inbox for public inquiries'],
            ['key' => 'contact_email_info', 'label' => 'Info email', 'type' => 'email', 'hint' => 'Optional second recipient'],
            ['key' => 'phone_primary', 'label' => 'Primary phone', 'type' => 'text'],
            ['key' => 'phone_secondary', 'label' => 'Secondary phone', 'type' => 'text'],
        ],
    ],
    [
        'id' => 'location',
        'title' => 'Location',
        'blurb' => 'Address and map link for visitors finding the office.',
        'fields' => [
            ['key' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 3],
            ['key' => 'map_url', 'label' => 'Map URL', 'type' => 'url', 'hint' => 'Google Maps or similar share link'],
        ],
    ],
    [
        'id' => 'social',
        'title' => 'Social',
        'blurb' => 'Profile links in the footer and contact strip.',
        'fields' => [
            ['key' => 'social_facebook', 'label' => 'Facebook', 'type' => 'url'],
            ['key' => 'social_twitter', 'label' => 'Twitter / X', 'type' => 'url'],
            ['key' => 'social_instagram', 'label' => 'Instagram', 'type' => 'url'],
            ['key' => 'social_linkedin', 'label' => 'LinkedIn', 'type' => 'url'],
        ],
    ],
    [
        'id' => 'giving',
        'title' => 'Giving',
        'blurb' => 'Donation form defaults and policy CTA wording.',
        'fields' => [
            ['key' => 'donation_min_amount', 'label' => 'Minimum amount', 'type' => 'text', 'hint' => 'Numeric minimum accepted'],
            ['key' => 'donation_currency', 'label' => 'Currency', 'type' => 'text', 'hint' => 'e.g. NGN'],
            ['key' => 'policy_cta_label', 'label' => 'Policy CTA label', 'type' => 'text'],
        ],
    ],
    [
        'id' => 'copy',
        'title' => 'Site copy',
        'blurb' => 'Mission and vision lines used on Home and About.',
        'fields' => [
            ['key' => 'home_mission', 'label' => 'Home mission', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'home_mission_support', 'label' => 'Home support line', 'type' => 'textarea', 'rows' => 2],
            ['key' => 'about_vision', 'label' => 'About vision', 'type' => 'textarea', 'rows' => 4],
        ],
    ],
    [
        'id' => 'seo',
        'title' => 'SEO defaults',
        'blurb' => 'Fallback title and description when a page has none.',
        'fields' => [
            ['key' => 'seo_default_title', 'label' => 'Default title', 'type' => 'text'],
            ['key' => 'seo_default_description', 'label' => 'Default description', 'type' => 'textarea', 'rows' => 3],
        ],
    ],
];
?>
<form method="post" action="<?= Str::e(Url::to('admin/settings')) ?>" class="settings-studio">
  <?= Csrf::field() ?>

  <div class="settings-layout">
    <nav class="settings-toc" aria-label="Settings sections">
      <?php foreach ($sections as $section): ?>
        <a href="#settings-<?= Str::e($section['id']) ?>"><?= Str::e($section['title']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="settings-panels">
      <?php foreach ($sections as $section): ?>
        <section class="settings-panel" id="settings-<?= Str::e($section['id']) ?>">
          <div class="settings-panel-shell">
            <div class="settings-panel-core">
              <header class="settings-panel-head">
                <h3><?= Str::e($section['title']) ?></h3>
                <p><?= Str::e($section['blurb']) ?></p>
              </header>

              <div class="settings-fields<?= count($section['fields']) > 1 ? ' is-grid' : '' ?>">
                <?php foreach ($section['fields'] as $field): ?>
                  <?php
                    $key = $field['key'];
                    $id = 'setting_' . $key;
                    $type = $field['type'];
                    $hint = $field['hint'] ?? null;
                    $span = in_array($type, ['textarea'], true) || in_array($key, ['org_name', 'address', 'map_url'], true);
                  ?>
                  <div class="settings-field<?= $span ? ' is-wide' : '' ?>">
                    <label for="<?= Str::e($id) ?>"><?= Str::e($field['label']) ?></label>
                    <?php if ($type === 'textarea'): ?>
                      <textarea
                        id="<?= Str::e($id) ?>"
                        name="<?= Str::e($key) ?>"
                        rows="<?= (int) ($field['rows'] ?? 3) ?>"
                      ><?= Str::e($v($key)) ?></textarea>
                    <?php else: ?>
                      <input
                        type="<?= Str::e($type) ?>"
                        id="<?= Str::e($id) ?>"
                        name="<?= Str::e($key) ?>"
                        value="<?= Str::e($v($key)) ?>"
                        autocomplete="off"
                      >
                    <?php endif; ?>
                    <?php if ($hint): ?>
                      <span class="settings-hint"><?= Str::e($hint) ?></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="settings-dock">
    <p class="settings-dock-note">Changes apply site-wide after you save.</p>
    <button class="btn settings-save" type="submit">
      Save settings
      <span class="btn-icon" aria-hidden="true">↗</span>
    </button>
  </div>
</form>
