<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var array<string, string|null> $settings */
/** @var array<string, array<string, mixed>> $sections */
/** @var list<array<string, mixed>> $programs */
/** @var list<array<string, mixed>> $testimonials */
/** @var list<array<string, mixed>> $sdgs */
/** @var list<array<string, mixed>> $announcements */
/** @var list<string> $heroSlides */
/** @var list<string> $presenceImages */
/** @var array<string, string> $programImages */
/** @var list<array{src:string,alt:string}> $fallbackAnnouncements */
/** @var string $aboutImage */
/** @var string|null $heroVideo */

$mission = $sections['hero']['body'] ?? ($settings['home_mission'] ?? '');
$support = $settings['home_mission_support'] ?? 'Join us in making the world a better place for all.';
$about = $sections['about_teaser']['body'] ?? '';
$logo = Url::asset('img/ADHERI-new-logo-1.png');
$orgShort = $settings['org_short_name'] ?? 'ADHERI';

$sdgLocal = [
    '1' => Url::asset('img/sdg/1.png'),
    '3' => Url::asset('img/sdg/3.png'),
    '5' => Url::asset('img/sdg/5.png'),
    '10' => Url::asset('img/sdg/10.png'),
    '13' => Url::asset('img/sdg/13.png'),
];
?>
<section class="hero-cinema" aria-label="ADHERI mission">
  <div class="hero-media" data-hero-media>
    <?php if (!empty($heroVideo)): ?>
      <div class="slide is-active" data-hero-slide>
        <video autoplay muted loop playsinline poster="<?= Str::e($heroSlides[0] ?? '') ?>">
          <source src="<?= Str::e($heroVideo) ?>" type="video/mp4">
        </video>
      </div>
    <?php else: ?>
      <?php foreach ($heroSlides as $i => $src): ?>
        <div class="slide<?= $i === 0 ? ' is-active' : '' ?>" data-hero-slide>
          <img src="<?= Str::e($src) ?>" alt="" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="hero-veil" aria-hidden="true"></div>

  <div class="hero-inner">
    <div class="hero-brand">
      <img src="<?= Str::e($logo) ?>" width="280" height="70" alt="<?= Str::e($orgShort) ?>">
    </div>
    <h1>End injustice. Protect dignity.</h1>
    <p class="support"><?= Str::e($mission) ?></p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="<?= Str::e(Url::to('about')) ?>">
        About us
        <span class="btn-icon" aria-hidden="true">↗</span>
      </a>
      <a class="btn btn-ghost" href="<?= Str::e(Url::to('donate')) ?>">Donate<span class="btn-icon" aria-hidden="true">↗</span></a>
    </div>
    <?php if (empty($heroVideo) && count($heroSlides) > 1): ?>
      <div class="hero-progress" data-hero-progress role="tablist" aria-label="Hero photography">
        <?php foreach ($heroSlides as $i => $_): ?>
          <button type="button" role="tab" aria-label="Show photo <?= $i + 1 ?>" data-hero-dot="<?= $i ?>"<?= $i === 0 ? ' class="is-active"' : '' ?>></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap about-editorial">
    <div class="about-copy" data-reveal>
      <h2>Women and youth-led advocacy for health and rights</h2>
      <p><?= Str::e($about) ?></p>
      <a class="btn btn-primary" href="<?= Str::e(Url::to('about')) ?>">
        Read our story
        <span class="btn-icon" aria-hidden="true">↗</span>
      </a>
    </div>
    <div class="bezel" data-reveal>
      <div class="bezel-inner about-frame">
        <img src="<?= Str::e($aboutImage) ?>" alt="ADHERI community programming" loading="lazy">
      </div>
    </div>
  </div>
</section>

<section class="section section-band">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <h2>Aligned to SDGs 1, 3, 5, 10, and 13</h2>
      <p class="lead">Our programs confront poverty, health inequity, gender-based violence, stigma, and climate harm facing women and youth in Nigeria.</p>
    </div>
    <div class="sdg-strip" data-reveal>
      <?php foreach ($sdgs as $sdg): ?>
        <?php
          $code = (string) $sdg['code'];
          $img = !empty($sdg['image_url'])
            ? Url::to(ltrim((string) $sdg['image_url'], '/'))
            : ($sdgLocal[$code] ?? null);
        ?>
        <div class="sdg-chip">
          <?php if ($img): ?>
            <img src="<?= Str::e($img) ?>" alt="" width="48" height="48" loading="lazy">
          <?php endif; ?>
          <span><?= Str::e((string) $sdg['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <h2>Presence in community</h2>
      <p class="lead">Documentary moments from trainings, artivism, and advocacy across ADHERI programming.</p>
    </div>
  </div>
  <div class="wrap">
    <div class="filmstrip" data-reveal>
      <?php foreach ($presenceImages as $src): ?>
        <figure class="film-card">
          <img src="<?= Str::e($src) ?>" alt="" loading="lazy">
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-band">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <h2>Programs &amp; announcements</h2>
      <p class="lead">Flyers and calls from advocacy, media, and artivism — swipe to explore.</p>
    </div>
    <div class="poster-rail" data-reveal>
      <?php if ($announcements !== []): ?>
        <?php foreach ($announcements as $item): ?>
          <?php $href = $item['link_url'] ?: '#'; ?>
          <a class="poster" href="<?= Str::e((string) $href) ?>" <?= !empty($item['link_url']) ? 'target="_blank" rel="noopener"' : '' ?>>
            <?php if (!empty($item['image_url'])): ?>
              <img src="<?= Str::e(Url::to(ltrim((string) $item['image_url'], '/'))) ?>" alt="<?= Str::e((string) ($item['alt_text'] ?: $item['title'] ?: 'Announcement')) ?>" loading="lazy">
            <?php else: ?>
              <span style="display:grid;place-items:center;height:100%;padding:1rem;font-weight:700"><?= Str::e((string) ($item['title'] ?: 'Announcement')) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach ($fallbackAnnouncements as $poster): ?>
          <div class="poster">
            <img src="<?= Str::e($poster['src']) ?>" alt="<?= Str::e($poster['alt']) ?>" loading="lazy">
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <h2>Dignity, respect, and opportunity</h2>
      <p class="lead">Seven program areas — open any to learn how ADHERI shows up for women and youth.</p>
    </div>
    <div class="program-bento" data-reveal>
      <?php foreach ($programs as $program): ?>
        <?php
          $slug = (string) $program['slug'];
          $img = $programImages[$slug] ?? Url::asset('img/programs/advocacy.jpg');
        ?>
        <a class="program-tile" href="<?= Str::e(Url::to('programs')) ?>#<?= Str::e($slug) ?>">
          <img src="<?= Str::e($img) ?>" alt="" loading="lazy">
          <div class="body">
            <h3><?= Str::e((string) $program['title']) ?></h3>
            <p><?= Str::e((string) ($program['summary'] ?? '')) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:1.75rem" data-reveal>
      <a class="btn btn-secondary" href="<?= Str::e(Url::to('programs')) ?>">See all programs<span class="btn-icon" aria-hidden="true">↗</span></a>
    </p>
  </div>
</section>

<section class="section section-band voice-section">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <h2>Voices from our community</h2>
      <p class="lead">Stories of courage, care, and change from people ADHERI walks with across Nigeria.</p>
    </div>
  </div>
  <?php if ($testimonials !== []): ?>
    <div class="voice-marquee" data-voice-marquee data-reveal>
      <div class="voice-marquee-track" data-voice-track>
        <?php for ($loop = 0; $loop < 2; $loop++): ?>
          <?php foreach ($testimonials as $item): ?>
            <?php
              $photo = trim((string) ($item['photo_path'] ?? ''));
              $photoUrl = $photo !== '' ? Url::media($photo) : '';
              $name = (string) $item['person_name'];
              $location = trim((string) ($item['location'] ?? ''));
            ?>
            <article class="voice-card"<?= $loop === 1 ? ' aria-hidden="true"' : '' ?>>
              <div class="voice-card-shell">
                <div class="voice-card-inner">
                  <div class="voice-stars" aria-label="5 out of 5 stars">
                    <?php for ($s = 0; $s < 5; $s++): ?>
                      <svg class="voice-star" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 2.5l2.85 5.77 6.37.93-4.61 4.49 1.09 6.35L12 17.77l-5.7 3 1.09-6.35-4.61-4.49 6.37-.93L12 2.5z"/></svg>
                    <?php endfor; ?>
                  </div>
                  <blockquote><?= Str::e((string) $item['quote']) ?></blockquote>
                  <footer class="voice-person">
                    <?php if ($photoUrl !== ''): ?>
                      <img class="voice-avatar" src="<?= Str::e($photoUrl) ?>" alt="" width="56" height="56" loading="lazy" decoding="async">
                    <?php else: ?>
                      <span class="voice-avatar voice-avatar--fallback" aria-hidden="true"><?= Str::e(mb_strtoupper(mb_substr($name, 0, 1))) ?></span>
                    <?php endif; ?>
                    <div class="voice-person-meta">
                      <cite><?= Str::e($name) ?></cite>
                      <?php if ($location !== ''): ?>
                        <span class="voice-location"><?= Str::e($location) ?></span>
                      <?php endif; ?>
                    </div>
                  </footer>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<section class="section section-ink">
  <div class="wrap closing" data-reveal>
    <div>
      <h2>Support the mission</h2>
      <p class="lead" style="color:rgba(232,238,244,0.72)">Donate, volunteer, or partner with ADHERI to end injustice and violence against vulnerable women and youth in Nigeria.</p>
    </div>
    <div class="hero-actions">
      <a class="btn btn-primary" href="<?= Str::e(Url::to('donate')) ?>">
        Donate
        <span class="btn-icon" aria-hidden="true">↗</span>
      </a>
      <a class="btn btn-ghost" href="<?= Str::e(Url::to('volunteer')) ?>">Volunteer<span class="btn-icon" aria-hidden="true">↗</span></a>
    </div>
  </div>
</section>
