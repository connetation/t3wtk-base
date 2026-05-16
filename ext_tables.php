<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Register backend layouts
$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] ??= '';
$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] .=
    '@import "EXT:conn_sitepackage/Configuration/TsConfig/Page/BackendLayouts.tsconfig"';
