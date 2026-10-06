<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $programs */
/** @var array<string, array<string, mixed>> $sections */
/** @var array<string, string> $programImages */
$programImages = $programImages ?? [];
?>
<section class="page-hero">
  <div class="wrap">
    <h1>Our Programs</h1>
    <p class="lead"><?= Str::e($sections['intro']['body'] ?? '') ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap program-stack">
    <?php foreach ($programs as $i => $program): ?>
      <?php
        $slug = (string) $program['slug'];
        $img = $programImages[$slug] ?? Url::asset('img/programs/advocacy.jpg');
        $reverse = $i % 2 === 1;
      ?>
      <article class="program-row<?= $reverse ? ' program-row--reverse' : '' ?>" id="<?= Str::e($slug) ?>">
        <div class="program-copy">
          <h3><?= Str::e((string) $program['title']) ?></h3>
          <p><?= nl2br(Str::e((string) ($program['body'] ?? $program['summary'] ?? ''))) ?></p>
        </div>
        <div class="program-visual">
          <img src="<?= Str::e($img) ?>" alt="" loading="lazy">
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
