<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bank;

use App\Bank\MerchantCatalog;
use App\Enum\Type\TransactionCategory;
use PHPUnit\Framework\TestCase;

final class MerchantCatalogTest extends TestCase
{
    public function testExposesAtLeastFiveHundredDistinctLabels(): void
    {
        $merchants = (new MerchantCatalog())->merchants();

        $labels = array_map(static fn (array $m): string => $m[0], $merchants);

        self::assertGreaterThanOrEqual(500, \count($labels));
        self::assertSame($labels, array_unique($labels), 'Les libellés de marchands sont uniques.');
    }

    public function testEachMerchantIsWellFormed(): void
    {
        foreach ((new MerchantCatalog())->merchants() as $merchant) {
            [$label, $category, $base, $spread] = $merchant;

            self::assertNotSame('', $label);
            self::assertInstanceOf(TransactionCategory::class, $category);
            self::assertGreaterThan(0, $base);
            self::assertGreaterThanOrEqual(0, $spread);
        }
    }
}
