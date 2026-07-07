<?php

namespace Tests\Unit;

use App\Support\WhatsApp;
use PHPUnit\Framework\TestCase;

class WhatsAppTest extends TestCase
{
    public function test_it_normalizes_nigerian_phone_formats(): void
    {
        $this->assertSame('2348031234567', WhatsApp::normalizePhone('08031234567'));
        $this->assertSame('2348031234567', WhatsApp::normalizePhone('+234 803 123 4567'));
        $this->assertSame('2348031234567', WhatsApp::normalizePhone('2348031234567'));
        $this->assertSame('2348031234567', WhatsApp::normalizePhone('8031234567'));
        $this->assertNull(WhatsApp::normalizePhone(null));
        $this->assertNull(WhatsApp::normalizePhone('   '));
    }

    public function test_it_builds_wa_me_links_with_encoded_message(): void
    {
        $link = WhatsApp::link('08031234567', "Hello parent\nYour balance is ₦5,000");

        $this->assertStringStartsWith('https://wa.me/2348031234567?text=', $link);
        $this->assertStringContainsString(rawurlencode('₦5,000'), $link);
        $this->assertNull(WhatsApp::link(null, 'message'));
    }
}
