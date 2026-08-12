<?php

declare(strict_types=1);

namespace Tei\PhotoSwipe\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tei\PhotoSwipe\LinkHandler\PhotoSwipeLinkBuilder;
use Tei\PhotoSwipe\LinkHandler\PhotoSwipeLinkHandler;
use Tei\PhotoSwipe\LinkHandler\PhotoSwipeLinkHandling;
use Tei\PhotoSwipe\Service\ImageService64;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Guards the dependency injection setup.
 *
 * Background: TYPO3 instantiates registered typolinkBuilders and link handlers
 * through GeneralUtility::makeInstance() WITHOUT arguments. That only resolves
 * through the DI container when the service is declared public. Miss that and
 * every frontend page dies with
 * "ArgumentCountError: Too few arguments to PageLinkBuilder::__construct()".
 */
final class ServiceRegistrationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/photoswipe',
    ];

    /**
     * @return array<string, array{class-string}>
     */
    public static function publicServiceProvider(): array
    {
        return [
            'link builder (frontend)' => [PhotoSwipeLinkBuilder::class],
            'link handler (backend link browser)' => [PhotoSwipeLinkHandler::class],
            'base64 preview service' => [ImageService64::class],
        ];
    }

    #[Test]
    #[DataProvider('publicServiceProvider')]
    public function serviceCanBeBuiltWithoutConstructorArguments(string $className): void
    {
        // Would throw an ArgumentCountError if the service were not public.
        self::assertInstanceOf($className, GeneralUtility::makeInstance($className));
    }

    #[Test]
    public function linkBuilderIsRegisteredForTheFrontend(): void
    {
        self::assertSame(
            PhotoSwipeLinkBuilder::class,
            $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['photoswipe'] ?? null
        );
    }

    #[Test]
    public function linkHandlingIsRegistered(): void
    {
        self::assertSame(
            PhotoSwipeLinkHandling::class,
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler']['photoswipe'] ?? null
        );
    }

    /**
     * Since TYPO3 14 the LinkFactory only calls buildLink() on builders that
     * implement TypolinkBuilderInterface. Overriding the old build() method
     * silently produced links without any data-pswp-* attributes.
     */
    #[Test]
    public function linkBuilderImplementsTheCurrentInterface(): void
    {
        self::assertInstanceOf(
            TypolinkBuilderInterface::class,
            GeneralUtility::makeInstance(PhotoSwipeLinkBuilder::class)
        );
    }

    #[Test]
    public function linkBuilderOverridesBuildLinkAndNotTheRemovedBuildMethod(): void
    {
        $reflection = new \ReflectionClass(PhotoSwipeLinkBuilder::class);

        self::assertSame(
            PhotoSwipeLinkBuilder::class,
            $reflection->getMethod('buildLink')->getDeclaringClass()->getName(),
            'buildLink() must be overridden, otherwise the layer attributes are lost.'
        );
        self::assertFalse(
            $reflection->hasMethod('build'),
            'build() was replaced by buildLink() in TYPO3 14 and must not linger.'
        );
    }
}
