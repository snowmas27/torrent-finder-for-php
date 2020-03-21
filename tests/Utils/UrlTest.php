<?php

namespace App\Tests\Utils;

use App\Utils\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @dataProvider provideBaseUrl
     */
    public function testGetBaseUrl(string $expected, Url $url): void
    {
        $this->assertSame($expected, $url->getBaseUrl());
    }

    public function provideBaseUrl(): iterable
    {
        yield ['http://google.com', Url::fromString('http://google.com/search=google')];
    }

    /**
     * @dataProvider provideUrl
     */
    public function testGetUrl(string $expected, Url $url): void
    {
        $this->assertSame($expected, $url->getUrl());
    }

    public function provideUrl(): iterable
    {
        yield ['https://www.torlock.com/all/torrents/%s.html?sort=seeds', Url::fromString('https://www.torlock.com/all/torrents/%s.html?sort=seeds')];
    }
}