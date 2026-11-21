<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Src\Contexts\Storefront\Application\DataObjects\SiteSettingsData;
use Src\Contexts\Storefront\Application\Queries\CourseCatalog;
use Src\Contexts\Storefront\Application\Queries\LandingStats;

final readonly class HomeController
{
    public function __construct(
        private CourseCatalog $catalog,
        private LandingStats $stats,
    ) {}

    public function __invoke(): View
    {
        /** @var SiteSettingsData $site */
        $site = view()->shared('site');

        $featured = $this->catalog->featured();

        return view('storefront::pages.home', [
            'levels' => $this->catalog->levels(),
            'featured' => $featured,
            'courses' => $this->catalog->latest(),
            'stats' => $site->showStats ? $this->stats->get() : null,
        ]);
    }
}
