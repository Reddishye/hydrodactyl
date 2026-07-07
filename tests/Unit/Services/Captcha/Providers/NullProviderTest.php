<?php

namespace Pterodactyl\Tests\Unit\Services\Captcha\Providers;

use Pterodactyl\Tests\TestCase;
use Pterodactyl\Services\Captcha\Providers\NullProvider;

class NullProviderTest extends TestCase
{
    private NullProvider $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->provider = new NullProvider();
    }

    /**
     * Test that the widget HTML is always an empty string.
     */
    public function testGetWidgetReturnsEmptyString()
    {
        $this->assertSame('', $this->provider->getWidget('default'));
        $this->assertSame('', $this->provider->getWidget('login'));
        $this->assertSame('', $this->provider->getWidget('custom'));
    }

    /**
     * Test that verification always returns true.
     */
    public function testVerifyAlwaysReturnsTrue()
    {
        $this->assertTrue($this->provider->verify('any-response'));
        $this->assertTrue($this->provider->verify(''));
        $this->assertTrue($this->provider->verify('test', '127.0.0.1'));
        $this->assertTrue($this->provider->verify('test', null));
    }

    /**
     * Test that script includes are always empty.
     */
    public function testGetScriptIncludesReturnsEmptyArray()
    {
        $this->assertSame([], $this->provider->getScriptIncludes());
        $this->assertIsArray($this->provider->getScriptIncludes());
    }

    /**
     * Test that the provider name is 'none'.
     */
    public function testGetNameReturnsNone()
    {
        $this->assertSame('none', $this->provider->getName());
    }

    /**
     * Test that the site key is always an empty string.
     */
    public function testGetSiteKeyReturnsEmptyString()
    {
        $this->assertSame('', $this->provider->getSiteKey());
    }

    /**
     * Test that the provider is always considered configured.
     */
    public function testIsConfiguredReturnsTrue()
    {
        $this->assertTrue($this->provider->isConfigured());
    }

    /**
     * Test that the response field name is always an empty string.
     */
    public function testGetResponseFieldNameReturnsEmptyString()
    {
        $this->assertSame('', $this->provider->getResponseFieldName());
    }
}
