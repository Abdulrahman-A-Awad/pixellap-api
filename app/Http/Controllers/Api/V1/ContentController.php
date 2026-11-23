<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\HomeSectionResource;
use App\Http\Resources\V1\PageResource;
use App\Http\Resources\V1\ServiceCategoryDetailResource;
use App\Http\Resources\V1\ServiceCategoryResource;
use App\Http\Resources\V1\SiteResource;
use App\Services\PublicContent;
use App\Support\ApiResponder;
use App\Support\CacheTags;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public read-only endpoints. Every response is cached per endpoint + params + locale
 * under the cache tags that invalidate it (App\Support\CacheTags).
 */
class ContentController extends Controller
{
    public function __construct(
        private readonly PublicContent $content,
        private readonly ApiResponder $responder,
    ) {}

    public function site(): JsonResponse
    {
        return $this->responder->cached('site', [CacheTags::SITE], fn () => new SiteResource($this->content));
    }

    public function home(): JsonResponse
    {
        // Home sections embed categories and services (the "services" section).
        return $this->responder->cached(
            'home',
            [CacheTags::HOME],
            fn () => HomeSectionResource::collection($this->content->homeSections()),
        );
    }

    public function page(string $key): JsonResponse
    {
        // Lookups run inside the closure: a cache hit skips the DB; a 404 throws and caches nothing.
        return $this->responder->cached(
            "pages/{$key}",
            [CacheTags::page($key)],
            fn () => new PageResource($this->content->page($key) ?? throw new NotFoundHttpException),
        );
    }

    public function categories(Request $request): JsonResponse
    {
        // ?include=services (services index) is a separate cache entry under the same tag.
        return $this->responder->cached(
            ServiceCategoryResource::includesServices($request) ? 'service-categories?include=services' : 'service-categories',
            [CacheTags::CATEGORIES],
            fn () => ServiceCategoryResource::collection($this->content->categories()),
        );
    }

    public function category(string $slug): JsonResponse
    {
        return $this->responder->cached(
            "service-categories/{$slug}",
            [CacheTags::category($slug)],
            fn () => new ServiceCategoryDetailResource($this->content->category($slug) ?? throw new NotFoundHttpException),
        );
    }

    public function sitemap(): JsonResponse
    {
        return $this->responder->cached('sitemap', [CacheTags::SITEMAP], fn () => $this->content->sitemap());
    }
}
