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

namespace Connetation\T3wtkBase\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * 301-redirects news list page 1 to the plain list URL, e.g. /news/1/ → /news/,
 * /news/some-category/1/ → /news/some-category/, ?tx_news_pi1[currentPage]=1&cHash=… → /news/.
 * Page 1 is the same content as the list without a page number, so the numbered URL is
 * duplicate content. Partials/News/List/Pagination.html never links to it; this catches
 * old links and bookmarks. Runs before the cHash check so stale query-string links redirect
 * instead of 404ing.
 */
final class NewsFirstPageRedirectMiddleware implements MiddlewareInterface
{
    private const NAMESPACE = 'tx_news_pi1';

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        $routing = $request->getAttribute('routing');
        if (!$site instanceof Site || !$routing instanceof PageArguments || !in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $handler->handle($request);
        }

        $arguments = $routing->getArguments();
        $pluginArguments = $arguments[self::NAMESPACE] ?? null;
        if (!is_array($pluginArguments) || (string)($pluginArguments['currentPage'] ?? '') !== '1') {
            return $handler->handle($request);
        }

        unset($pluginArguments['currentPage']);
        // Without currentPage, a bare list action/controller is the page default — drop them
        // so the router generates the plain page URL instead of a query string.
        if (($pluginArguments['action'] ?? 'list') === 'list' && ($pluginArguments['controller'] ?? 'News') === 'News') {
            unset($pluginArguments['action'], $pluginArguments['controller']);
        }
        unset($arguments[self::NAMESPACE], $arguments['cHash']);
        if ($pluginArguments !== []) {
            $arguments[self::NAMESPACE] = $pluginArguments;
        }
        $arguments['_language'] = $request->getAttribute('language');

        $target = (string)$site->getRouter()->generateUri($routing->getPageId(), $arguments);
        if ($target === '' || $target === (string)$request->getUri()) {
            return $handler->handle($request);
        }

        return $this->responseFactory->createResponse(301)
            ->withHeader('Location', $target)
            ->withHeader('X-Redirect-By', 'conn_t3wtk_base news first page');
    }
}
