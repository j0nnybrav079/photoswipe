<?php

declare(strict_types=1);

namespace Tei\PhotoSwipe\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * Creates the base64 encoded preview images used for lazy loading.
 */
class ImageService64
{
    /** @var array<string, mixed> */
    protected array $settings = [];

    private const DEFAULT_SETTINGS = [
        'imageExtensions' => 'jpg,jpeg,png,gif,webp',
        'svgWidth' => '40',
    ];

    public function __construct(
        private readonly FrontendConfigurationManager $frontendConfigurationManager,
        private readonly ImageService $imageService,
    ) {
    }

    public function getBase64Preview(ProcessedFile $processedImage): string
    {
        $this->settings = $this->getSettings();

        $imageExtension = strtolower($processedImage->getExtension());
        $allowedImageExtensions = GeneralUtility::trimExplode(
            ',',
            strtolower((string)$this->settings['imageExtensions']),
            true
        );

        if (!in_array($imageExtension, $allowedImageExtensions, true)) {
            return 'data:image/file-extensions-not-supported;';
        }

        $properties = $processedImage->getOriginalFile()->getProperties();
        $width = (int)($properties['width'] ?? 0);
        $height = (int)($properties['height'] ?? 0);
        if ($width === 0 || $height === 0) {
            return 'data:image/file-extensions-not-supported;';
        }

        $svgWidth = (int)$this->settings['svgWidth'];
        $processedImageSVG = $this->imageService->applyProcessingInstructions($processedImage, [
            'width' => $svgWidth,
            'height' => (int)round(($height * $svgWidth) / $width),
        ]);

        $fileLink = Environment::getPublicPath() . $this->imageService->getImageUri($processedImageSVG);

        // Strip EXIF data and read the raw image bytes. The temporary file name
        // includes the process id so parallel requests cannot overwrite each other.
        $tmpFileLink = Environment::getVarPath() . '/transient/ps_tmp_' . getmypid() . '.' . $imageExtension;
        GeneralUtility::mkdir_deep(dirname($tmpFileLink));
        $this->removeExif($fileLink, $tmpFileLink);

        $tmpData = @file_get_contents($tmpFileLink);
        @unlink($tmpFileLink);

        if ($tmpData === false || $tmpData === '') {
            return 'data:image/file-extensions-not-supported;';
        }

        return 'data:image/' . $imageExtension . ';base64,' . base64_encode($tmpData);
    }

    /**
     * Since TYPO3 13 getTypoScriptSetup() requires the request. Outside of a
     * frontend request (for example while rendering a backend preview) there is
     * no frontend TypoScript, so the defaults apply.
     *
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return self::DEFAULT_SETTINGS;
        }

        try {
            $setup = $this->frontendConfigurationManager->getTypoScriptSetup($request);
        } catch (\Throwable) {
            return self::DEFAULT_SETTINGS;
        }

        $settings = $setup['plugin.']['tx_photoswipe.']['settings.'] ?? [];

        return array_replace(self::DEFAULT_SETTINGS, array_filter(
            [
                'imageExtensions' => $settings['imageExtensions'] ?? null,
                'svgWidth' => $settings['svgWidth'] ?? null,
            ],
            static fn($value) => $value !== null && $value !== ''
        ));
    }

    /**
     * taken from https://stackoverflow.com/questions/3614925/remove-exif-data-from-jpg-using-php/38862429 [Dmitry Bugrov]
     */
    private function removeExif(string $in, string $out): void
    {
        $bufferLength = 4096;
        $fdIn = @fopen(urldecode($in), 'rb');
        if ($fdIn === false) {
            return;
        }
        $fdOut = @fopen($out, 'wb');
        if ($fdOut === false) {
            fclose($fdIn);
            return;
        }

        while (($buffer = fread($fdIn, $bufferLength))) {
            //  \xFF\xE1\xHH\xLLExif\x00\x00 - Exif
            //  \xFF\xE1\xHH\xLLhttp://      - XMP
            //  \xFF\xE2\xHH\xLLICC_PROFILE  - ICC
            //  \xFF\xED\xHH\xLLPhotoshop    - PH
            while (preg_match('/\xFF[\xE1\xE2\xED\xEE](.)(.)(exif|photoshop|http:|icc_profile|adobe)/si', $buffer, $match, PREG_OFFSET_CAPTURE)) {
                $len = ord($match[1][0]) * 256 + ord($match[2][0]);
                fwrite($fdOut, substr($buffer, 0, $match[0][1]));
                $filepos = $match[0][1] + 2 + $len - strlen($buffer);
                fseek($fdIn, $filepos, SEEK_CUR);
                $buffer = fread($fdIn, $bufferLength);
            }
            fwrite($fdOut, $buffer, strlen($buffer));
        }

        fclose($fdOut);
        fclose($fdIn);
    }
}
