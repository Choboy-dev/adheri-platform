<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Settings\SettingsRepository;
use Adheri\Support\Flash;
use Adheri\Support\Url;

final class SettingsController extends Controller
{
    public function save(Request $request): never
    {
        $this->requireCsrf($request);
        $repo = new SettingsRepository();
        $pairs = [];
        foreach (SettingsRepository::ADMIN_KEYS as $key) {
            $pairs[$key] = $request->string($key);
        }
        $repo->setMany($pairs);
        Flash::success('Settings saved.');
        Response::redirect(Url::to('admin/settings'));
    }
}
