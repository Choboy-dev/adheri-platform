<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
?>
<section class="section" style="padding-top:8rem">
  <div class="wrap prose">
    <h1>Page not found</h1>
    <p>The page you requested is not available.</p>
    <p><a class="btn btn-primary" href="<?= Str::e(Url::to('/')) ?>">Back home<span class="btn-icon" aria-hidden="true">↗</span></a></p>
  </div>
</section>
