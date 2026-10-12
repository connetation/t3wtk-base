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

namespace Connetation\T3wtkBase\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Warms frontend caches by crawling every page URL of the site(s).
 *
 * Because the crawl issues real frontend requests, everything a visitor
 * would trigger gets built: page cache, compiled Fluid templates and all
 * image renditions (srcset ladders / WebP) that are not processed yet.
 *
 * URL sources, in order:
 *   1. sitemap.xml per site language (typo3/cms-seo) — respects no_index etc.
 *   2. Fallback: standard pages from the page tree, routed per language.
 *
 * Requests run sequentially on purpose: first-render image processing is
 * CPU-bound (ImageMagick), and parallel warmup can starve a small server.
 *
 * Usage:
 *   vendor/bin/typo3 conn:warmup
 *   vendor/bin/typo3 conn:warmup --site=main
 *   vendor/bin/typo3 conn:warmup --insecure   (self-signed certs, e.g. DDEV)
 */
#[AsCommand(
    name: 'conn:warmup',
    description: 'Warm frontend page caches and image renditions by crawling all site URLs'
)]
final class CacheWarmupCommand extends Command
{
    private const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly RequestFactory $requestFactory,
        private readonly ConnectionPool $connectionPool,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('site', 's', InputOption::VALUE_REQUIRED, 'Site identifier to warm (default: all sites)')
            ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'Per-request timeout in seconds', '120')
            ->addOption('insecure', 'k', InputOption::VALUE_NONE, 'Skip TLS certificate verification (self-signed certs)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $timeout = max(5, (int)$input->getOption('timeout'));
        $verify = !$input->getOption('insecure');

        $siteIdentifier = (string)($input->getOption('site') ?? '');
        if ($siteIdentifier !== '') {
            try {
                $sites = [$this->siteFinder->getSiteByIdentifier($siteIdentifier)];
            } catch (SiteNotFoundException) {
                $io->error(sprintf('Site "%s" not found.', $siteIdentifier));
                return Command::FAILURE;
            }
        } else {
            $sites = array_values($this->siteFinder->getAllSites());
        }

        if ($sites === []) {
            $io->error('No sites configured.');
            return Command::FAILURE;
        }

        $totalOk = 0;
        $totalFailed = 0;
        $failures = [];

        foreach ($sites as $site) {
            $io->section(sprintf('Site "%s" (%s)', $site->getIdentifier(), (string)$site->getBase()));

            $urls = $this->collectFromSitemaps($site, $timeout, $verify);
            if ($urls === []) {
                $io->writeln('No sitemap found — falling back to page-tree crawl.');
                $urls = $this->collectFromPageTree($site);
            }
            $urls = array_values(array_unique($urls));

            if ($urls === []) {
                $io->warning('No URLs found for this site.');
                continue;
            }

            $io->progressStart(count($urls));
            foreach ($urls as $url) {
                $start = microtime(true);
                try {
                    $response = $this->requestFactory->request($url, 'GET', [
                        'timeout' => $timeout,
                        'verify' => $verify,
                        'headers' => ['User-Agent' => 'conn-t3wtk-base-cache-warmup'],
                        'http_errors' => false,
                    ]);
                    $status = $response->getStatusCode();
                    if ($status >= 400) {
                        $totalFailed++;
                        $failures[] = sprintf('%s → HTTP %d', $url, $status);
                    } else {
                        $totalOk++;
                    }
                } catch (\Throwable $e) {
                    $totalFailed++;
                    $failures[] = sprintf('%s → %s', $url, $e->getMessage());
                }
                if ($output->isVerbose()) {
                    $io->writeln(sprintf(' %s (%.1fs)', $url, microtime(true) - $start));
                }
                $io->progressAdvance();
            }
            $io->progressFinish();
        }

        $io->newLine();
        $io->writeln(sprintf('Warmed: <info>%d</info>  Failed: %s', $totalOk, $totalFailed > 0 ? "<error>$totalFailed</error>" : '0'));
        foreach ($failures as $failure) {
            $io->writeln('  <comment>' . $failure . '</comment>');
        }

        return ($totalOk === 0 && $totalFailed > 0) ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Collects page URLs from sitemap.xml of every site language.
     * Handles both <sitemapindex> (typo3/cms-seo default) and plain <urlset>.
     *
     * @return string[]
     */
    private function collectFromSitemaps(Site $site, int $timeout, bool $verify): array
    {
        $urls = [];
        foreach ($site->getLanguages() as $language) {
            $sitemapUrl = rtrim((string)$language->getBase(), '/') . '/sitemap.xml';
            $xml = $this->fetchXml($sitemapUrl, $timeout, $verify);
            if ($xml === null) {
                continue;
            }

            if ($xml->getName() === 'sitemapindex') {
                foreach ($this->extractLocs($xml, 'sitemap') as $childSitemapUrl) {
                    $childXml = $this->fetchXml($childSitemapUrl, $timeout, $verify);
                    if ($childXml !== null) {
                        $urls = [...$urls, ...$this->extractLocs($childXml, 'url')];
                    }
                }
            } else {
                $urls = [...$urls, ...$this->extractLocs($xml, 'url')];
            }
        }
        return $urls;
    }

    private function fetchXml(string $url, int $timeout, bool $verify): ?\SimpleXMLElement
    {
        try {
            $response = $this->requestFactory->request($url, 'GET', [
                'timeout' => $timeout,
                'verify' => $verify,
                'headers' => ['User-Agent' => 'conn-t3wtk-base-cache-warmup'],
                'http_errors' => false,
            ]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            $xml = @simplexml_load_string((string)$response->getBody());
            return $xml instanceof \SimpleXMLElement ? $xml : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    private function extractLocs(\SimpleXMLElement $xml, string $entryName): array
    {
        $locs = [];
        $entries = $xml->children(self::SITEMAP_NS)->{$entryName};
        if (count($entries) === 0) {
            $entries = $xml->{$entryName}; // sitemap without default namespace
        }
        foreach ($entries as $entry) {
            $loc = trim((string)$entry->children(self::SITEMAP_NS)->loc ?: (string)$entry->loc);
            if ($loc !== '') {
                $locs[] = $loc;
            }
        }
        return $locs;
    }

    /**
     * Fallback: all standard pages below the site root, routed per language.
     *
     * @return string[]
     */
    private function collectFromPageTree(Site $site): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $rows = $queryBuilder
            ->select('uid', 'pid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('doktype', 1)
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // Resolve which pages sit inside this site's root
        $childrenByPid = [];
        foreach ($rows as $row) {
            $childrenByPid[(int)$row['pid']][] = (int)$row['uid'];
        }
        $pageIds = [$site->getRootPageId()];
        $queue = [$site->getRootPageId()];
        while ($queue !== []) {
            $pid = array_shift($queue);
            foreach ($childrenByPid[$pid] ?? [] as $childUid) {
                $pageIds[] = $childUid;
                $queue[] = $childUid;
            }
        }

        $urls = [];
        foreach ($pageIds as $pageId) {
            foreach ($site->getLanguages() as $language) {
                try {
                    $urls[] = (string)$site->getRouter()->generateUri($pageId, ['_language' => $language->getLanguageId()]);
                } catch (\Throwable) {
                    // page not available in this language — skip
                }
            }
        }
        return $urls;
    }
}
