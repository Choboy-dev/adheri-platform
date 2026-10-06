<?php

declare(strict_types=1);

namespace Adheri\Modules\Sdgs;

use Adheri\Database;

final class SdgRepository
{
    /** @return list<array<string, mixed>> */
    public function published(): array
    {
        return Database::fetchAll(
            'SELECT s.*, m.url_path AS image_url
             FROM sdg_badges s
             LEFT JOIN media_assets m ON m.id = s.image_id
             WHERE s.is_published = 1
             ORDER BY s.sort_order ASC, s.id ASC'
        );
    }
}
