<?php

namespace Tests\Unit\Services;

use App\Services\RdapDomainService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RdapDomainServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('rdap_dns_bootstrap');
    }

    public function test_parses_expiration_from_rdap_response(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response([
                'services' => [
                    [['com'], ['https://rdap.example.com/v1/']],
                ],
            ], 200),
            'https://rdap.example.com/v1/domain/example.com' => Http::response([
                'events' => [
                    ['eventAction' => 'registration', 'eventDate' => '2020-01-01T00:00:00Z'],
                    ['eventAction' => 'expiration', 'eventDate' => '2027-06-01T00:00:00Z'],
                ],
                'entities' => [
                    [
                        'roles' => ['registrar'],
                        'vcardArray' => ['vcard', [['fn', [], 'text', 'Example Registrar Inc']]],
                    ],
                ],
            ], 200),
        ]);

        $result = (new RdapDomainService())->lookup('example.com');

        $this->assertSame('rdap', $result['source']);
        $this->assertSame('2027-06-01', $result['expires_at']);
        $this->assertArrayNotHasKey('error', $result);
    }
}
