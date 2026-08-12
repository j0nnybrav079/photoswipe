<?php

declare(strict_types=1);

namespace Tei\PhotoSwipe\Backend\EventListener;

use TYPO3\CMS\Backend\Form\Event\ModifyLinkExplanationEvent;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * Marks a link as a PhotoSwipe layer in the backend form.
 */
final class LinkEventListener
{
    public function __construct(
        private readonly IconFactory $iconFactory
    ) {
    }

    public function __invoke(ModifyLinkExplanationEvent $event): void
    {
        if (($event->getLinkData()['type'] ?? null) !== 'photoswipe') {
            return;
        }

        $url = (string)($event->getLinkParts()['url'] ?? '');
        $isContentLayer = str_contains($url, '#');

        $event->setLinkExplanation([
            // Icon::SIZE_SMALL was replaced by the IconSize enum in TYPO3 14.
            'icon' => $this->iconFactory->getIcon(
                $isContentLayer ? 'tx-photoswipe-content' : 'tx-photoswipe-page',
                IconSize::SMALL
            )->render(),
            'text' => sprintf(
                'PhotoSwipe Lightbox - %s (%s)',
                $isContentLayer ? 'Content' : 'Page',
                $url
            ),
        ]);
    }
}
