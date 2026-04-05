<?php

declare(strict_types=1);

namespace Tei\PhotoSwipe\Service;

use TYPO3\CMS\Core\Attribute\AsAllowedCallable;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class ATagParams
{
    private ContentObjectRenderer $cObj;

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    #[AsAllowedCallable]
    public function set(string $content, array $conf): string
    {
        $file = $this->cObj->getCurrentFile();

        if (!$file) {
            return '';
        }

        ['width' => $width, 'height' => $height] = $this->getFileProperties($file);

        // fallback to original file if dimensions are still null for unknown reason
        if (((int)$width === 0) || ((int)$height === 0)) {
            ['width' => $width, 'height' => $height] = $file->getOriginalFile()->getProperties();
        }

        return sprintf(
            'data-ispsw-img="1" data-pswp-width="%s" data-pswp-height="%s"',
            $width,
            $height,
        );
    }

    private function getFileProperties($file): array
    {
        $crop = $file->getReferenceProperties()['crop'] ?? null;

        if ($crop === null) {
            return $file->getProperties();
        }

        $cropArea = CropVariantCollection::create($crop)->getCropArea('default');

        return $cropArea->isEmpty() ? $file->getProperties() : $this->getCroppedFileProperties($file, $cropArea);
    }

    private function getCroppedFileProperties($file, Area $cropArea): array
    {
        $processingInstructions = [
            'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($file)
        ];

        $imageService = GeneralUtility::makeInstance(ImageService::class);

        return $imageService->applyProcessingInstructions($file, $processingInstructions)->getProperties();
    }
}