<?php

namespace Tests\Unit\Support;

use App\Support\StripeSubscriptionMetadata;
use Stripe\Util\RequestOptions;
use Tests\TestCase;

class StripeSubscriptionMetadataTest extends TestCase
{
    /** @test */
    public function stringify_keeps_only_scalar_string_values(): void
    {
        $result = StripeSubscriptionMetadata::stringify([
            'client_id' => 'uuid-123',
            'site_id'   => 'uuid-456',
            'bad'       => new RequestOptions,
            'nested'    => ['array'],
        ]);

        $this->assertSame([
            'client_id' => 'uuid-123',
            'site_id'   => 'uuid-456',
        ], $result);
    }

    /** @test */
    public function casting_stripe_object_to_array_would_break_stripe_api(): void
    {
        // Regression: (array) $stripeMetadata caused RequestOptions to string conversion errors.
        $polluted = array_merge(
            ['_opts' => new RequestOptions],
            ['client_id' => 'ok'],
        );

        $this->assertArrayHasKey('_opts', $polluted);
        $this->assertArrayNotHasKey('_opts', StripeSubscriptionMetadata::stringify($polluted));
    }
}
