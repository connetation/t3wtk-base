<?php

declare(strict_types=1);

namespace Connetation\T3wtkBase\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * A heading whose tag (SEO level) and visual size are configurable — Fluid
 * can't build a tag name from a variable, so this renders it.
 *
 *   <conn:heading tag="{settings.appearance.titleTag}" size="h4" class="card-title" itemprop="headline">
 *       <a href="…">Title</a>
 *   </conn:heading>
 *   → <h3 class="h4 card-title" itemprop="headline"><a href="…">Title</a></h3>
 *
 * tag: h1–h6, p or div (anything else → $default); size: h1–h6 visual-size
 * class put in front of the other classes ("none" or empty → no size class).
 * Any other argument (class, itemprop, data-…) becomes a tag attribute.
 */
final class HeadingViewHelper extends AbstractTagBasedViewHelper
{
    private const TAGS  = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div'];
    private const SIZES = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('tag', 'string', 'Tag: h1-h6, p or div', false, '');
        $this->registerArgument('default', 'string', 'Tag used when "tag" is empty or invalid', false, 'h3');
        $this->registerArgument('size', 'string', 'Visual size class h1-h6 ("none"/empty = none)', false, '');
    }

    public function render(): string
    {
        $tag = (string)$this->arguments['tag'];
        if (!\in_array($tag, self::TAGS, true)) {
            $tag = \in_array($this->arguments['default'], self::TAGS, true) ? (string)$this->arguments['default'] : 'h3';
        }
        $this->tag->setTagName($tag);

        $size  = \in_array($this->arguments['size'], self::SIZES, true) ? (string)$this->arguments['size'] : '';
        $class = trim($size . ' ' . (string)$this->tag->getAttribute('class'));
        if ($class !== '') {
            $this->tag->addAttribute('class', preg_replace('/\s+/', ' ', $class));
        }

        $this->tag->setContent((string)$this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
