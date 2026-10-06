<?php

use Adheri\Support\Seo;
use Adheri\Support\Str;

/** @var array<string, string|null> $settings */
/** @var string $title */
/** @var array<string, mixed>|null $meta */

$settings = $settings ?? [];
$seo = Seo::meta($settings, (string) ($title ?? ''), is_array($meta ?? null) ? $meta : []);
?>
<meta name="description" content="<?= Str::e($seo['description']) ?>">
<link rel="canonical" href="<?= Str::e($seo['url']) ?>">

<meta property="og:type" content="<?= Str::e($seo['type']) ?>">
<meta property="og:site_name" content="<?= Str::e($seo['site_name']) ?>">
<meta property="og:locale" content="<?= Str::e($seo['locale']) ?>">
<meta property="og:title" content="<?= Str::e($seo['title']) ?>">
<meta property="og:description" content="<?= Str::e($seo['description']) ?>">
<meta property="og:url" content="<?= Str::e($seo['url']) ?>">
<meta property="og:image" content="<?= Str::e($seo['image']) ?>">
<meta property="og:image:alt" content="<?= Str::e($seo['title']) ?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= Str::e($seo['title']) ?>">
<meta name="twitter:description" content="<?= Str::e($seo['description']) ?>">
<meta name="twitter:image" content="<?= Str::e($seo['image']) ?>">
