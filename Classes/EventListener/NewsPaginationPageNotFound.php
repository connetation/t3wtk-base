<?php

declare(strict_types=1);

namespace Connetation\T3kitBase\EventListener;

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
#[AsEventListener('conn-t3kit-base/news-pagination-page-not-found', event: NewsListPostPaginationEvent::class)]
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
