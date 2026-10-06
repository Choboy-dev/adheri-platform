<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var array<string, string|null> $settings */
/** @var list<array<string, mixed>> $board */
/** @var list<array<string, mixed>> $youth */
/** @var array<string, array<string, mixed>> $sections */
/** @var string|null $aboutImage */
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">About</p>
    <h1>A world where everyone’s voice is heard</h1>
    <p class="lead"><?= Str::e($settings['about_vision'] ?? '') ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap split">
    <div>
      <h2>Who we are</h2>
      <p><?= Str::e($sections['story']['body'] ?? '') ?></p>
      <p>We are a women and youth-led organisation operating by feminist leadership principles — focused on stigma, human rights violations, GBV, substance misuse, and climate impacts on women and youth.</p>
    </div>
    <div class="bezel about-bezel">
      <div class="bezel-inner about-frame">
        <img src="<?= Str::e($aboutImage ?? Url::asset('img/about/About-us-image.jpg')) ?>" alt="ADHERI community" loading="lazy">
      </div>
    </div>
  </div>
</section>

<section class="section section-band">
  <div class="wrap">
    <div class="section-head">
      <h2>Our mission</h2>
      <p class="lead">Empower minority and vulnerable youth and amplify their voices through feminist, evidence-based, community-centred work — using media, art, culture, and storytelling for social change.</p>
    </div>
    <div class="values-grid">
      <?php
      $values = [
        'Community Driven' => 'Programs curated for the communities where we work.',
        'Accountability' => 'Honest, transparent evaluation with partners, donors, and communities.',
        'Confidentiality' => 'Strong privacy for clients, beneficiaries, and anonymous benefactors.',
        'No Stigma' => 'Respect for intersecting identities and equal rights for all genders.',
        'Collaborative' => 'Trusting partnerships and alliances for progressive change.',
        'Time' => 'Timely interventions so impact meets the season of need.',
      ];
      foreach ($values as $title => $copy): ?>
        <article class="value-card">
          <h3><?= Str::e($title) ?></h3>
          <p><?= Str::e($copy) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Leadership</p>
      <h2>Board of Trustees</h2>
    </div>
    <div class="people-grid">
      <?php foreach ($board as $person): ?>
        <article class="person-card">
          <div class="person-photo" aria-hidden="true"></div>
          <div class="meta">
            <h3><?= Str::e((string) $person['name']) ?></h3>
            <div class="role"><?= Str::e((string) ($person['role_title'] ?? 'Trustee')) ?></div>
            <?php if (!empty($person['bio'])): ?><p><?= Str::e(Str::excerpt((string) $person['bio'], 180)) ?></p><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-muted">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Next generation</p>
      <h2>Women-led Youth Advisory Board</h2>
    </div>
    <div class="people-grid">
      <?php foreach ($youth as $person): ?>
        <article class="person-card">
          <div class="person-photo" aria-hidden="true"></div>
          <div class="meta">
            <h3><?= Str::e((string) $person['name']) ?></h3>
            <div class="role">Youth Advisory Board</div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:2rem" class="hero-actions">
      <a class="btn btn-primary" href="<?= Str::e(Url::to('donate')) ?>">Support us<span class="btn-icon" aria-hidden="true">↗</span></a>
      <a class="btn btn-secondary" href="<?= Str::e(Url::to('volunteer')) ?>">Volunteer<span class="btn-icon" aria-hidden="true">↗</span></a>
    </p>
  </div>
</section>
