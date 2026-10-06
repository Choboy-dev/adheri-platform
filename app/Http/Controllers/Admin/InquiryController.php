<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Inquiries\InquiryRepository;
use Adheri\Support\Flash;
use Adheri\Support\Url;

final class InquiryController extends Controller
{
    public function updateStatus(Request $request, string $id): never
    {
        $this->requireCsrf($request);
        $inquiryId = (int) $id;
        $status = $request->string('status');
        $repo = new InquiryRepository();

        if ($repo->find($inquiryId) === null) {
            Flash::error('Inquiry not found.');
            Response::redirect(Url::to('admin/inquiries'));
        }

        if (!$repo->updateStatus($inquiryId, $status)) {
            Flash::error('Invalid status.');
            Response::redirect(Url::to('admin/inquiries'));
        }

        Flash::success('Inquiry updated.');
        Response::redirect(Url::to('admin/inquiries'));
    }
}
