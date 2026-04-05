<?php

namespace Tei\PhotoSwipe\ViewHelpers;

use Tei\PhotoSwipe\Service\ImageService64;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Render a given image.
 */
class Base64ImageViewHelper extends AbstractTagBasedViewHelper
{
    /** @var string */
    protected $tagName = 'img';

    /** @var ImageService */
    protected $imageService;

    /** @var ImageService64 */
    protected $imageService64;

    /**
     * @param ImageService $imageService
     */
    public function injectImageService(ImageService $imageService): void
    {
        $this->imageService = $imageService;
    }

    /**
     * @param ImageService64 $imageService64
     * @return void
     */
    public function injectImageService64(ImageService64 $imageService64): void
    {
        $this->imageService64 = $imageService64;
    }

    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        // registerUniversalTagAttributes() was removed in typo3fluid/fluid 5.x (TYPO3 v14)
        $this->registerTagAttribute('class', 'string', 'CSS class(es) for this element');
        $this->registerTagAttribute('id', 'string', 'Unique (in this file) identifier for this HTML element');
        $this->registerTagAttribute('lang', 'string', 'Language for this element');
        $this->registerTagAttribute('style', 'string', 'Individual CSS styles for this element');
        $this->registerTagAttribute('title', 'string', 'Tooltip text of element');
        $this->registerTagAttribute('accesskey', 'string', 'Keyboard shortcut to access this element');
        $this->registerTagAttribute('tabindex', 'integer', 'Specifies the tab order of this element');
        $this->registerTagAttribute('onclick', 'string', 'JavaScript evaluated for the onclick event');
        $this->registerArgument('url', 'string', 'a path to the image', true, '');
    }

    /**
     * Resizes a given image (if required) and renders the respective img tag
     *
     * @throws Exception
     * @return string Rendered tag
     */
    public function render(): string
    {
        $image = $this->imageService->getImage($this->arguments['url'], null, false);
        return (string)$this->imageService64->getBase64Preview(
            $this->imageService->applyProcessingInstructions($image, [])
        );
    }
}