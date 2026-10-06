<?php

declare(strict_types=1);

namespace Connetation\T3wtkBase\PageTitle;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\Page\PageInformation;

/**
 * Appends the page number to the title of paginated news list pages ("News – Seite 2"),
 * so page 2+ don't share one <title> with page 1. Page 1 keeps the plain title.
 * Registered with the highest priority in Configuration/Sets/News/setup.typoscript;
 * returns '' (= next provider wins) everywhere else.
 */
final class NewsPaginationPageTitleProvider extends AbstractPageTitleProvider
{
    private const LABEL = 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang.xlf:pagination.pageTitle';

    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    public function getTitle(): string
    {
        $routing = $this->request?->getAttribute('routing');
        $arguments = $routing instanceof PageArguments ? $routing->getArguments() : [];
        $currentPage = (int)($arguments['tx_news_pi1']['currentPage'] ?? 0);
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
