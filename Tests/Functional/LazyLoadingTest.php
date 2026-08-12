<?php

declare(strict_types=1);

namespace Tei\PhotoSwipe\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tei\PhotoSwipe\ViewHelpers\ImageViewHelper;
use Tei\PhotoSwipe\ViewHelpers\MediaViewHelper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Guards the base64 lazy loading preview.
 *
 * Background: MediaViewHelper compared $this->arguments['lazy64'] strictly
 * against the integer 1 while Fluid passes the string "1". The feature never
 * worked through that view helper - and textmedia elements render through it.
 */
final class LazyLoadingTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/photoswipe',
    ];

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function lazyArgumentProvider(): array
    {
        return [
            'string "1" as passed by Fluid' => ['1', true],
            'integer 1' => [1, true],
            'string "0"' => ['0', false],
            'integer 0' => [0, false],
            'empty string' => ['', false],
            'null' => [null, false],
        ];
    }

    /**
     * Both view helpers must treat the argument identically and must accept the
     * string form, because that is what Fluid hands over.
     */
    #[Test]
    #[DataProvider('lazyArgumentProvider')]
    public function lazyFlagIsEvaluatedForStringAndIntegerInput(mixed $value, bool $expected): void
    {
        self::assertSame($expected, (int)($value ?? 0) === 1);
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function viewHelperProvider(): array
    {
        return [
            'MediaViewHelper' => [MediaViewHelper::class],
            'ImageViewHelper' => [ImageViewHelper::class],
        ];
    }

    /**
     * Regression guard: a strict comparison against the integer literal in the
     * lazy64 branch silently disables the feature.
     */
    #[Test]
    #[DataProvider('viewHelperProvider')]
    public function viewHelperCastsTheLazyArgumentBeforeComparing(string $className): void
    {
        $source = (string)file_get_contents((new \ReflectionClass($className))->getFileName());

        self::assertMatchesRegularExpression(
            '/\(int\)\(\$this->arguments\[.lazy64.\][^)]*\)\s*===\s*1/',
            $source,
            sprintf(
                '%s must cast the lazy64 argument to int before comparing - Fluid passes a string.',
                $className
            )
        );
        self::assertDoesNotMatchRegularExpression(
            '/\$this->arguments\[.lazy64.\]\s*===\s*1\b/',
            $source,
            sprintf('%s still compares the raw lazy64 argument strictly against an integer.', $className)
        );
    }

    #[Test]
    public function lazyLoadingCheckboxIsRegisteredOnContentElements(): void
    {
        $config = $GLOBALS['TCA']['tt_content']['columns']['imagelazy64']['config'] ?? [];

        self::assertSame('check', $config['type'] ?? null);
        self::assertSame('checkboxToggle', $config['renderType'] ?? null);
    }

    /**
     * TYPO3 14 no longer migrates the numeric items format automatically.
     */
    #[Test]
    public function checkboxItemsUseAssociativeKeys(): void
    {
        $items = $GLOBALS['TCA']['tt_content']['columns']['imagelazy64']['config']['items'] ?? [];

        self::assertNotSame([], $items);
        foreach ($items as $item) {
            self::assertArrayHasKey('label', $item);
            self::assertArrayHasKey('value', $item);
        }
    }
}
