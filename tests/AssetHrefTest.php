<?php

declare(strict_types=1);

namespace Lvt\Tests;

final class AssetHrefTest extends TestCase
{
    public function testVersionComesFromFileMtimeAndFallsBack(): void
    {
        $css = (string) filemtime(LVT_ROOT . '/public/assets/css/app.css');
        $js = (string) filemtime(LVT_ROOT . '/public/assets/js/app.js');
        $this->assertSame('/assets/css/app.css?v=' . $css, asset_href('/assets/css/app.css'));
        $this->assertSame('/assets/js/app.js?v=' . $js, asset_href('/assets/js/app.js'));
        $this->assertSame('/assets/css/missing.css?v=' . LVT_ASSET_VERSION, asset_href('/assets/css/missing.css'));
        $this->assertSame('/assets/../.env?v=' . LVT_ASSET_VERSION, asset_href('/assets/../.env'));
    }

    public function testLayoutsLinkVersionedStylesheetAndScript(): void
    {
        $css = '/assets/css/app.css?v=' . filemtime(LVT_ROOT . '/public/assets/css/app.css');
        $js = '/assets/js/app.js?v=' . filemtime(LVT_ROOT . '/public/assets/js/app.js');
        $client = $this->client();

        $home = $client->get('/');
        $this->assertSame(200, $home->status);
        $this->assertStringContainsString('href="' . $css . '"', $home->body);
        $this->assertStringContainsString('src="' . $js . '"', $home->body);
        $this->assertDoesNotMatchRegularExpression('/href="\/assets\/css\/app\.css"/', $home->body);
        $this->assertDoesNotMatchRegularExpression('/src="\/assets\/js\/app\.js"/', $home->body);

        $login = $client->get('/login');
        $this->assertSame(200, $login->status);
        $this->assertStringContainsString('href="' . $css . '"', $login->body);

        $staff = $this->makeStaff('owner', 'assets@example.test');
        $this->signIn($client, $staff);
        $admin = $client->get('/admin');
        $this->assertSame(200, $admin->status);
        $this->assertStringContainsString('href="' . $css . '"', $admin->body);
        $this->assertStringContainsString('src="' . $js . '"', $admin->body);
    }
}
