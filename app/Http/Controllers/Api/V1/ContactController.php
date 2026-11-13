<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Services\ContactInbox;
use App\Services\PublicContent;
use App\Support\ApiResponder;
use App\Support\ClientIp;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __invoke(
        StoreContactRequest $request,
        ContactInbox $inbox,
        PublicContent $content,
        ApiResponder $responder,
    ): JsonResponse {
        if (! $request->isSpam()) {
            $inbox->receive($request->validated(), ClientIp::resolve($request), $request->userAgent());
        }

        return $responder->make(
            ['message' => $content->uiString('form.success', 'Thank you. Your message has been sent.')],
            JsonResponse::HTTP_CREATED,
        );
    }
}
