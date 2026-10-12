<?php

declare(strict_types=1);

/*
 * This file is part of the Connetation TYPO3 Web Toolkit - T3WTK.
 *
 * Copyright (c) Connetation Web Engineering GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Connetation\T3wtkBase\EventListener;

use GeorgRinger\News\Event\NewsListPostPaginationEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * 404 for news list page numbers beyond the last page. EXT:news clamps them to the last
 * page, so /news/3/ … /news/100/ (anything the route's StaticRangeMapper allows) would all
 * return 200 with the same content — duplicate pages crawlers find and report.
 * Only active when EXT:news is installed (the event is never dispatched otherwise).
 */
#[AsEventListener('conn-t3wtk-base/news-pagination-page-not-found', event: NewsListPostPaginationEvent::class)]
final class NewsPaginationPageNotFound
{
    public function __construct(
        private readonly ErrorController $errorController,
    ) {}

    public function __invoke(NewsListPostPaginationEvent $event): void
    {
        $assignedPagination = $event->getAssignedPagination();
        $requestedPage = (int)($assignedPagination['currentPage'] ?? 1);
        $paginator = $assignedPagination['paginator'] ?? null;
        if ($requestedPage <= 1 || !$paginator instanceof PaginatorInterface) {
            return;
        }

        if ($requestedPage > max(1, $paginator->getNumberOfPages())) {
            throw new PropagateResponseException(
                $this->errorController->pageNotFoundAction($event->getRequest(), 'News list page ' . $requestedPage . ' does not exist'),
                1790600001
            );
        }
    }
}
