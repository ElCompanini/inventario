<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SearchControllerSafetyTest extends TestCase
{
    public function test_global_search_does_not_query_removed_product_description_column(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Http/Controllers/SearchController.php');
        $productBlock = substr(
            $source,
            strpos($source, '$productos ='),
            strpos($source, '$sicds =') - strpos($source, '$productos =')
        );

        $this->assertStringNotContainsString('descripcion', $productBlock);
    }
}
