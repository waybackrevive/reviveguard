<?php

namespace Tests\Unit\Services;

use App\Services\WhoisSocketService;
use PHPUnit\Framework\TestCase;

class WhoisSocketServiceTest extends TestCase
{
    public function test_parses_verisign_style_expiry(): void
    {
        $raw = <<<'WHOIS'
Domain Name: NASIMIYUDESIGNS.COM
Registrar: GoDaddy.com, LLC
Registry Expiry Date: 2027-03-15T11:20:33Z
WHOIS;

        $result = (new WhoisSocketService())->parse('nasimiyudesigns.com', $raw);

        $this->assertSame('2027-03-15', $result['expires_at']);
        $this->assertSame('whois', $result['source']);
        $this->assertStringContainsString('GoDaddy', $result['registrar'] ?? '');
    }

    public function test_parses_registrar_expiration_label(): void
    {
        $raw = <<<'WHOIS'
Registrar Registration Expiration Date: 2026-12-01T00:00:00Z
Registrar Name: Namecheap
WHOIS;

        $result = (new WhoisSocketService())->parse('example.com', $raw);

        $this->assertSame('2026-12-01', $result['expires_at']);
    }
}
