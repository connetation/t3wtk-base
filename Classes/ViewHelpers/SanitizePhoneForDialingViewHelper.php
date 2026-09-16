<?php

declare(strict_types=1);

namespace Connetation\Base\ViewHelpers;

use Connetation\Base\Utility\PhoneUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Usage:
 *   <a href="tel:{phone -> conn:sanitizePhoneForDialing()}">{phone}</a>
 *   <a href="tel:{conn:sanitizePhoneForDialing(value: phone)}">{phone}</a>
 *
 * Keep the raw `{phone}` for the visible label — only the `href` needs the
 * dial-safe version.
 */
final class SanitizePhoneForDialingViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'Human-formatted phone number', false, null);
    }

    public function render(): string
    {
        $phone = $this->arguments['value'] ?? $this->renderChildren();

        return PhoneUtility::sanitizeForDialing((string)$phone);
    }
}
