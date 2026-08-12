<?php

declare(strict_types=1);

namespace Tei\PhotoSwipe\LinkHandler;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;

/**
 * Builds the frontend links that open a PhotoSwipe layer.
 *
 * Since TYPO3 14 the LinkFactory no longer calls build() but buildLink()
 * (TypolinkBuilderInterface). The ContentObjectRenderer is now passed as the
 * request attribute "currentContentObject" instead of $GLOBALS['TSFE']->cObj.
 */
class PhotoSwipeLinkBuilder extends PageLinkBuilder
{
    public function buildLink(
        array $linkDetails,
        array $configuration,
        ServerRequestInterface $request,
        string $linkText = ''
    ): LinkResultInterface {
        $result = parent::buildLink($linkDetails, $configuration, $request, $linkText)
            ->withAttribute('data-ispsw-layer', '1');

        $url = $result->getUrl();
        $contentUid = explode('#', $url)[1] ?? null;

        return $contentUid !== null && $contentUid !== ''
            ? $this->buildContentLayer($result, $contentUid, $request)
            : $this->buildIFrameLayer($result, $url);
    }

    private function buildIFrameLayer(LinkResultInterface $result, string $url): LinkResultInterface
    {
        return $result
            ->withAttribute('data-pswp-type', 'iframe')
            ->withAttribute('data-iframe-url', $url);
    }

    private function buildContentLayer(
        LinkResultInterface $result,
        string $contentUid,
        ServerRequestInterface $request
    ): LinkResultInterface {
        $contentObjectRenderer = $request->getAttribute('currentContentObject');
        if (!$contentObjectRenderer instanceof ContentObjectRenderer) {
            // Without a cObj the content cannot be rendered. Fall back to a plain
            // link instead of throwing in the middle of the page rendering.
            return $result;
        }

        $html = (string)$contentObjectRenderer->cObjGetSingle(
            'RECORDS',
            [
                'tables' => 'tt_content',
                'source' => (int)ltrim($contentUid, 'c'),
                'dontCheckPid' => 1,
            ]
        );
        $html = trim((string)preg_replace('/\s+/', ' ', $html));

        if ($html === '') {
            return $result;
        }

        return $result
            ->withAttribute('data-pswp-type', 'html')
            ->withAttribute('data-html', rawurlencode($html));
    }
}
