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

namespace Connetation\T3wtkBase\PageTitle;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\Page\PageInformation;

/**
 * Appends the page number to the title of paginated list pages ("News – Seite 2"),
 * so page 2+ don't share one <title> with page 1. Page 1 keeps the plain title.
 * Subclasses name the plugin whose `currentPage` argument is read; they are
 * registered with the highest priority in the News/Events sets and return ''
 * (= next provider wins) everywhere else.
 */
abstract class AbstractPaginationPageTitleProvider extends AbstractPageTitleProvider
{
    private const LABEL = 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang.xlf:pagination.pageTitle';

    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /** Plugin argument namespace holding `currentPage`, e.g. tx_news_pi1. */
    abstract protected function getArgumentNamespace(): string;

    public function getTitle(): string
    {
        $routing = $this->request?->getAttribute('routing');
        $arguments = $routing instanceof PageArguments ? $routing->getArguments() : [];
        $currentPage = (int)($arguments[$this->getArgumentNamespace()]['currentPage'] ?? 0);
        if ($currentPage < 2) {
            return '';
        }

        $pageInformation = $this->request->getAttribute('frontend.page.information');
        $page = $pageInformation instanceof PageInformation ? $pageInformation->getPageRecord() : [];
        $title = trim((string)(($page['seo_title'] ?? '') ?: ($page['title'] ?? '')));
        if ($title === '') {
            return '';
        }

        $language = $this->request->getAttribute('language');
        $languageService = $language instanceof SiteLanguage
            ? $this->languageServiceFactory->createFromSiteLanguage($language)
            : $this->languageServiceFactory->create('default');
        $pattern = $languageService->sL(self::LABEL) ?: '%1$s – Page %2$d';

        return sprintf($pattern, $title, $currentPage);
    }
}
