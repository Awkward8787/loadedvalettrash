<?php

declare(strict_types=1);

namespace Lvt\Tests;

final class PublicSiteTest extends TestCase
{
    public function testHomePageStatesTheOfferWithoutInventedClaims(): void
    {
        $client = $this->client();
        $home = $client->get('/');
        $this->assertSame(200, $home->status);
        $this->assertStringContainsString('Valet Trash in Valdosta, GA', $home->body);
        $this->assertStringContainsString('Book a walkthrough', $home->body);
        $this->assertStringContainsString('Request a proposal', $home->body);
        $this->assertStringContainsString('No published reviews yet', $home->body);
        $this->assertStringNotContainsString('[ADD', $home->body);
        $this->assertStringNotContainsString('Trusted by', $home->body);
        $this->assertStringNotContainsString('lorem', strtolower($home->body));
        $this->assertStringNotContainsString('$1,000,000', $home->body);
        $this->assertStringNotContainsString('$2,000,000', $home->body);
        $this->assertDoesNotMatchRegularExpression('/href="tel:/', $home->body);
        $this->assertDoesNotMatchRegularExpression('/mailto:/', $home->body);
    }

    public function testRobotsAndCorePagesExist(): void
    {
        $client = $this->client();
        foreach (['/how-it-works', '/pricing', '/who-we-serve', '/reviews', '/faq', '/about', '/contact', '/missed-pickup', '/privacy', '/terms'] as $path) {
            $response = $client->get($path);
            $this->assertSame(200, $response->status, $path);
            $this->assertStringNotContainsString('[ADD', $response->body, $path);
        }
        $robots = $client->get('/robots.txt');
        $this->assertStringContainsString('Disallow: /admin', $robots->body);
        $this->assertStringContainsString('Disallow: /login', $robots->body);
    }
}
