<?php

namespace Tests\Unit\Support;

use App\Support\StripePriceId;
use PHPUnit\Framework\TestCase;

class StripePriceIdTest extends TestCase
{
    public function test_accepts_valid_price_id(): void
    {
        $this->assertTrue(StripePriceId::isValid('price_1AbCdEfGhIjKlMnO'));
        $this->assertNull(StripePriceId::describeProblem('price_1AbCdEfGhIjKlMnO', 'Monitor plan'));
    }

    public function test_rejects_product_id_with_clear_message(): void
    {
        $this->assertFalse(StripePriceId::isValid('prod_Upl40H24iBHE0E'));

        $message = StripePriceId::describeProblem('prod_Upl40H24iBHE0E', 'Guard plan');

        $this->assertNotNull($message);
        $this->assertStringContainsString('Product ID', $message);
        $this->assertStringContainsString('price_', $message);
    }

    public function test_rejects_unknown_prefix(): void
    {
        $this->assertFalse(StripePriceId::isValid('sub_12345'));
        $this->assertStringContainsString('must start with price_', StripePriceId::describeProblem('sub_12345', 'Shield plan'));
    }
}
