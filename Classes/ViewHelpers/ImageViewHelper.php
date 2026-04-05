<?php

namespace Tei\PhotoSwipe\ViewHelpers;

use Tei\PhotoSwipe\Service\ImageService64;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Render a given image.
 */
class ImageViewHelper extends AbstractTagBasedViewHelper
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
        $this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an image', false);
        $this->registerTagAttribute('ismap', 'string', 'Specifies an image as a server-side image-map. Rarely used. Look at usemap instead', false);
        $this->registerTagAttribute('longdesc', 'string', 'Specifies the URL to a document that contains a long description of an image', false);
        $this->registerTagAttribute('usemap', 'string', 'Specifies an image as a client-side image-map', false);
        $this->registerTagAttribute('loading', 'string', 'Native lazy-loading for images property. Can be "lazy", "eager" or "auto"', false);

        $this->registerArgument('lazy64', 'string', 'Base64 based lazy-loading  for images property. Can be "0", or "1". Used on image files only.');
        $this->registerArgument('src', 'string', 'a path to a file, a combined FAL identifier or an uid (int). If $treatIdAsReference is set, the integer is considered the uid of the sys_file_reference record. If you already got a FAL object, consider using the $image parameter instead', false, '');
        $this->registerArgument('treatIdAsReference', 'bool', 'given src argument is a sys_file_reference record', false, false);
        $this->registerArgument('image', 'object', 'a FAL object');
        $this->registerArgument('crop', 'string|bool', 'overrule cropping of image (setting to FALSE disables the cropping set in FileReference)');
        $this->registerArgument('cropVariant', 'string', 'select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Custom file extension to use');

        $this->registerArgument('width', 'string', 'width of the image.');
        $this->registerArgument('height', 'string', 'height of the image.');
        $this->registerArgument('minWidth', 'int', 'minimum width of the image');
        $this->registerArgument('minHeight', 'int', 'minimum height of the image');
        $this->registerArgument('maxWidth', 'int', 'maximum width of the image');
        $this->registerArgument('maxHeight', 'int', 'maximum height of the image');
        $this->registerArgument('absolute', 'bool', 'Force absolute URL', false, false);
    }

    /**
     * Resizes a given image (if required) and renders the respective img tag
     *
     * @throws Exception
     * @return string Rendered tag
     */
    public function render(): string
    {
        $src = (string)($this->arguments['src'] ?? '');
        if (($src === '' && $this->arguments['image'] === null) || ($src !== '' && $this->arguments['image'] !== null)) {
            throw new Exception('You must either specify a string src or a File object.', 1382284106);
        }

        if ($src !== '' && preg_match('/^(https?:)?\/\//', $src)) {
            $this->tag->addAttribute('src', $src);
            if (isset($this->arguments['width'])) {
                $this->tag->addAttribute('width', $this->arguments['width']);
            }
            if (isset($this->arguments['height'])) {
                $this->tag->addAttribute('height', $this->arguments['height']);
            }
        } else {
            try {
                $image = $this->imageService->getImage($src, $this->arguments['image'], (bool)$this->arguments['treatIdAsReference']);
                $cropString = $this->arguments['crop'];
                if ($cropString === null && $image->hasProperty('crop') && $image->getProperty('crop')) {
                    $cropString = $image->getProperty('crop');
                }
                $cropVariantCollection = CropVariantCollection::create((string)$cropString);
                $cropVariant = $this->arguments['cropVariant'] ?: 'default';
                $cropArea = $cropVariantCollection->getCropArea($cropVariant);
                $processingInstructions = [
                    'width' => $this->arguments['width'],
                    'height' => $this->arguments['height'],
                    'minWidth' => $this->arguments['minWidth'],
                    'minHeight' => $this->arguments['minHeight'],
                    'maxWidth' => $this->arguments['maxWidth'],
                    'maxHeight' => $this->arguments['maxHeight'],
                    'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image),
                ];
                if (!empty($this->arguments['fileExtension'] ?? '')) {
                    $processingInstructions['fileExtension'] = $this->arguments['fileExtension'];
                }
                $processedImage = $this->imageService->applyProcessingInstructions($image, $processingInstructions);
                $imageUri = $this->imageService->getImageUri($processedImage, $this->arguments['absolute']);

                if (!$this->tag->hasAttribute('data-focus-area')) {
                    $focusArea = $cropVariantCollection->getFocusArea($cropVariant);
                    if (!$focusArea->isEmpty()) {
                        $this->tag->addAttribute('data-focus-area', $focusArea->makeAbsoluteBasedOnFile($image));
                    }
                }
                if (isset($this->arguments['lazy64']) && (int)$this->arguments['lazy64'] === 1) {
                    $this->tag->addAttribute('src', $this->imageService64->getBase64Preview($processedImage));
                    $this->tag->addAttribute('data-src', $imageUri);
                    $cssClass = $this->arguments['class'] ? 'lazy64 ' . $this->arguments['class'] : 'lazy64';
                    $this->tag->addAttribute('class', $cssClass);
                } else {
                    $this->tag->addAttribute('src', $imageUri);
                }
                $this->tag->addAttribute('width', $processedImage->getProperty('width'));
                $this->tag->addAttribute('height', $processedImage->getProperty('height'));

                if (empty($this->arguments['alt'])) {
                    $this->tag->addAttribute('alt', $image->hasProperty('alternative') ? $image->getProperty('alternative') : '');
                }
                $title = $image->hasProperty('title') ? $image->getProperty('title') : '';
                if (empty($this->arguments['title']) && $title !== '') {
                    $this->tag->addAttribute('title', $title);
                }
            } catch (ResourceDoesNotExistException $e) {
                throw new Exception($e->getMessage(), 1509741911, $e);
            } catch (\Exception $e) {
                throw new Exception($e->getMessage(), 1509741912, $e);
            }
        }
        return (string)$this->tag->render();
    }
}