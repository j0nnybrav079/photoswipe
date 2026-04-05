<?php

namespace Tei\PhotoSwipe\ViewHelpers;


use Tei\PhotoSwipe\Service\ImageService64;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Render a given media file with the correct html tag.
 *
 * It asks the :php:`RendererRegistry` for the correct Renderer class and if not found it falls
 * back to the :php:`ImageViewHelper` as that is the "Renderer" class for images in Fluid context.
 *
 * Examples
 * ========
 *
 * Image Object
 * ------------
 *
 * ::
 *
 *    <f:media file="{file}" width="400" height="375" />
 *
 * Output::
 *
 *    <img alt="alt set in image record" src="fileadmin/_processed_/323223424.png" width="396" height="375" />
 *
 * MP4 Video Object
 * ----------------
 *
 * ::
 *
 *    <f:media file="{file}" width="400" height="375" />
 *
 * Output::
 *
 *    <video width="400" height="375" controls><source src="fileadmin/user_upload/my-video.mp4" type="video/mp4"></video>
 *
 * MP4 Video Object with loop and autoplay option set
 * --------------------------------------------------
 *
 * ::
 *
 *    <f:media file="{file}" width="400" height="375" additionalConfig="{loop: '1', autoplay: '1'}" />
 *
 * Output::
 *
 *    <video width="400" height="375" controls loop><source src="fileadmin/user_upload/my-video.mp4" type="video/mp4"></video>
 */
final class MediaViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'img';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        // Note: registerTagAttribute() and registerUniversalTagAttributes() were removed
        // in typo3fluid/fluid 5.x (TYPO3 v14). HTML attributes like class, id, style etc.
        // are passed automatically via additionalAttributes or as tag attributes by the framework.
        $this->registerArgument('alt', 'string', 'Specifies an alternate text for an image', false);
        $this->registerArgument('file', 'object', 'File', true);
        $this->registerArgument('additionalConfig', 'array', 'This array can hold additional configuration that is passed though to the Renderer object', false, []);
        $this->registerArgument('width', 'string', 'This can be a numeric value representing the fixed width of in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.');
        $this->registerArgument('height', 'string', 'This can be a numeric value representing the fixed height in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.');
        $this->registerArgument('cropVariant', 'string', 'select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Custom file extension to use for images');
        $this->registerArgument('loading', 'string', 'Native lazy-loading for images property. Can be "lazy", "eager" or "auto". Used on image files only.');
        $this->registerArgument('decoding', 'string', 'Provides an image decoding hint to the browser. Can be "sync", "async" or "auto"', false);
        $this->registerArgument('lazy64', 'string', 'Base64 based lazy-loading  for images property. Can be "0", or "1". Used on image files only.');
    }

    /**
     * Render a given media file.
     *
     * @throws \UnexpectedValueException
     * @throws Exception
     */
    public function render(): string
    {
        $file = $this->arguments['file'] ?? null;
        $additionalConfig = (array)($this->arguments['additionalConfig'] ?? []);
        $width = ($this->arguments['width'] ?? 0);
        $height = ($this->arguments['height'] ?? 0);

        // get Resource Object (non ExtBase version)
        if (is_callable([$file, 'getOriginalResource'])) {
            // We have a domain model, so we need to fetch the FAL resource object from there
            $file = $file->getOriginalResource();
        }

        if (!$file instanceof FileInterface) {
            throw new \UnexpectedValueException('Supplied file object type ' . get_class($file) . ' must be FileInterface.', 1454252193);
        }

        if ((string)($this->arguments['fileExtension'] ?? '') && !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], (string)$this->arguments['fileExtension'])) {
            throw new Exception(
                'The extension ' . $this->arguments['fileExtension'] . ' is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'
                . ' as a valid image file extension and can not be processed.',
                1619030957
            );
        }

        $fileRenderer = GeneralUtility::makeInstance(RendererRegistry::class)->getRenderer($file);

        // Fallback to image when no renderer is found
        if ($fileRenderer === null) {
            return $this->renderImage($file, $width, $height, $this->arguments['fileExtension'] ?? null);
        }
        $arguments = [];
        foreach ($this->arguments as $argumentName => $argumentValue) {
            // Prevent "null" when given in fluid
            if (!empty($argumentValue) && $argumentValue !== 'null') {
                $arguments[$argumentName] = $argumentValue;
            }
        }
        $additionalConfig = array_merge_recursive($arguments, $additionalConfig);
        return $fileRenderer->render($file, $width, $height, $additionalConfig);
    }

    /**
     * Render img tag
     *
     * @param string $width
     * @param string $height
     * @return string Rendered img tag
     */
    protected function renderImage(FileInterface $image, $width, $height, ?string $fileExtension): string
    {
        $cropVariant = (string)(($this->arguments['cropVariant'] ?? '') ?: 'default');
        $cropString = $image instanceof FileReference ? $image->getProperty('crop') : '';
        $cropVariantCollection = CropVariantCollection::create((string)$cropString);
        $cropArea = $cropVariantCollection->getCropArea($cropVariant);
        $processingInstructions = [
            'width' => $width,
            'height' => $height,
            'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image),
        ];
        if (!empty($fileExtension)) {
            $processingInstructions['fileExtension'] = $fileExtension;
        }
        $imageService = $this->getImageService();
        $processedImage = $imageService->applyProcessingInstructions($image, $processingInstructions);
        $imageUri = $imageService->getImageUri($processedImage);

        if (!$this->tag->hasAttribute('data-focus-area')) {
            $focusArea = $cropVariantCollection->getFocusArea($cropVariant);
            if (!$focusArea->isEmpty()) {
                $this->tag->addAttribute('data-focus-area', $focusArea->makeAbsoluteBasedOnFile($image));
            }
        }

        if (isset($this->arguments['lazy64']) && $this->arguments['lazy64'] === 1) {
            $this->tag->addAttribute('src', $this->getImageService64()->getBase64Preview($processedImage));
            $this->tag->addAttribute('data-src', $imageUri);
            $cssClass = $this->arguments['class'] ? 'lazy64 ' . $this->arguments['class'] : 'lazy64';
            $this->tag->addAttribute('class', $cssClass);
        } else {
            $this->tag->addAttribute('src', $imageUri);
        }


        $this->tag->addAttribute('width', $processedImage->getProperty('width'));
        $this->tag->addAttribute('height', $processedImage->getProperty('height'));
        if (in_array($this->arguments['loading'] ?? '', ['lazy', 'eager', 'auto'], true)) {
            $this->tag->addAttribute('loading', $this->arguments['loading']);
        }
        if (in_array($this->arguments['decoding'] ?? '', ['sync', 'async', 'auto'], true)) {
            $this->tag->addAttribute('decoding', $this->arguments['decoding']);
        }

        $alt = $image->getProperty('alternative');
        $title = $image->getProperty('title');

        // The alt-attribute is mandatory to have valid html-code, therefore add it even if it is empty
        if (empty($this->arguments['alt'])) {
            $this->tag->addAttribute('alt', $alt);
        }
        if (empty($this->arguments['title']) && !empty($title)) {
            $this->tag->addAttribute('title', $title);
        }

        return $this->tag->render();
    }

    protected function getImageService(): ImageService
    {
        return GeneralUtility::makeInstance(ImageService::class);
    }
    protected function getImageService64(): ImageService64
    {
        return GeneralUtility::makeInstance(ImageService64::class);
    }
}






