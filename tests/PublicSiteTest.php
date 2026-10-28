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
        $this->assertStringContainsString('Valet Trash in Valdosta / Lowndes County, Atlanta, and Tampa', $home->body);
        $this->assertStringContainsString('Serving apartment communities in Valdosta / Lowndes County, Atlanta, and Tampa.', $home->body);
        $this->assertStringContainsString('Atlanta, GA', $home->body);
        $this->assertStringContainsString('Tampa, FL', $home->body);
        $this->assertStringNotContainsString('Hahira', $home->body);
        $this->assertStringNotContainsString('Remerton', $home->body);
        $this->assertStringContainsString('$35', $home->body);
        $this->assertStringContainsString('The 30-day trial is free on the buildings you pick.', $home->body);
        $this->assertStringContainsString('Book a walkthrough', $home->body);
        $this->assertStringContainsString('Request a proposal', $home->body);
        $this->assertStringContainsString('Quoted per event', $home->body);
        $this->assertStringContainsString('Quoted per job', $home->body);
        $this->assertStringContainsString('Never put a loose bag on the ground', $home->body);
        $this->assertStringContainsString('No published reviews yet', $home->body);
        $this->assertStringContainsString('6:00 to 8:00 PM', $home->body);
        $this->assertStringContainsString('8:00 to 10:00 PM', $home->body);
        $this->assertStringNotContainsString('–', $home->body);
        $this->assertStringNotContainsString('—', $home->body);
        $this->assertStringNotContainsString('[ADD', $home->body);
        $this->assertStringNotContainsString('Trusted by', $home->body);
        $this->assertStringNotContainsString('lorem', strtolower($home->body));
        $this->assertStringNotContainsString('$1,000,000', $home->body);
        $this->assertStringNotContainsString('$2,000,000', $home->body);
        $this->assertDoesNotMatchRegularExpression('/href="tel:/', $home->body);
        $this->assertDoesNotMatchRegularExpression('/mailto:/', $home->body);
        $this->assertMatchesRegularExpression(
            '/<video\b(?![^>]*\bautoplay\b)[^>]*\bcontrols\b[^>]*\bplaysinline\b[^>]*\bpreload="none"[^>]*\bposter="\/assets\/media\/lvt-commercial-poster\.jpg"/',
            $home->body
        );
        $this->assertStringContainsString('aria-label="Loaded Valet Trash commercial. Porters collect bagged trash from the can outside each door, Monday through Friday."', $home->body);
        $this->assertStringContainsString('<source src="/assets/media/lvt-commercial-720p.mp4" type="video/mp4">', $home->body);
        $this->assertStringContainsString('<track kind="captions" src="/assets/media/lvt-commercial.en.vtt" srclang="en" label="English" default>', $home->body);
        $this->assertDoesNotMatchRegularExpression('/<video\b[^>]*\bautoplay\b/', $home->body);
        $this->assertStringNotContainsString('(229) 595-8852', $home->body);
        $this->assertStringNotContainsString('229-595-8852', $home->body);
        $captions = (string) file_get_contents(dirname(__DIR__) . '/public/assets/media/lvt-commercial.en.vtt');
        $this->assertStringContainsString('Loaded Valet Trash. Call (229) 595-8852.', $captions);
        $htaccess = (string) file_get_contents(dirname(__DIR__) . '/public/.htaccess');
        $this->assertStringContainsString("AddType text/vtt .vtt", $htaccess);
        $this->assertStringContainsString('RewriteRule ^ index.php [L]', $htaccess);
        $root = (string) file_get_contents(dirname(__DIR__) . '/.htaccess');
        $this->assertStringContainsString('bin|templates|bootstrap|docs|vendor', $root);
    }

    public function testRobotsAndCorePagesExist(): void
    {
        $client = $this->client();
        foreach (['/services', '/how-it-works', '/pricing', '/who-we-serve', '/reviews', '/faq', '/about', '/contact', '/careers', '/missed-pickup', '/privacy', '/terms'] as $path) {
            $response = $client->get($path);
            $this->assertSame(200, $response->status, $path);
            $this->assertStringNotContainsString('[ADD', $response->body, $path);
            $this->assertStringNotContainsString('$1,000,000', $response->body, $path);
            $this->assertStringNotContainsString('$2,000,000', $response->body, $path);
            $this->assertStringNotContainsString('Place bags at the door', $response->body, $path);
            $this->assertStringContainsString('Valdosta / Lowndes County, Atlanta, and Tampa', $response->body, $path);
        }
        $how = $client->get('/how-it-works');
        $this->assertStringContainsString('On extra-bag nights, extra bags go only on top of the can, never on the ground or beside it.', $how->body);
        $this->assertStringContainsString('Extra bags go only on top of the can, never on the ground or beside it.', $how->body);
        $faq = $client->get('/faq');
        $this->assertStringContainsString('On extra-bag nights, extra bags go only on top of the can, never on the ground or beside it.', $faq->body);
        $this->assertStringContainsString('On those extra-bag nights, extra bags go only on top of the can, never on the ground or beside it.', $faq->body);
        $robots = $client->get('/robots.txt');
        $this->assertStringContainsString('Disallow: /admin', $robots->body);
        $this->assertStringContainsString('Disallow: /login', $robots->body);
    }
}
