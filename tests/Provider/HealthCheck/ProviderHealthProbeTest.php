<?php

namespace TorrentFinder\Tests\Provider\HealthCheck;

use PHPUnit\Framework\TestCase;
use TorrentFinder\Provider\HealthCheck\ProviderHealthProbe;
use TorrentFinder\Provider\Jackett\JackettUrlBuilder;
use TorrentFinder\Provider\ProviderConfiguration;
use TorrentFinder\Provider\ProviderInformation;
use TorrentFinder\Provider\GenericProvider;
use TorrentFinder\Search\SearchQuery;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\Utils\Url;
use TorrentFinder\VideoSettings\Resolution;

class ProviderHealthProbeTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'probe');
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    public function testProviderAnsweringWithoutResultIsHealthy(): void
    {
        file_put_contents($this->file, '<html><body>no result</body></html>');

        $this->probe()->probeProvider($this->configuration(new Url('file', '', $this->file, '')), $this->query());

        $this->addToAssertionCount(1);
    }

    public function testUnreachableProviderThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->probe()->probeProvider($this->configuration(Url::fromString('http://127.0.0.1:1/search?q=%s')), $this->query());
    }

    public function testUnreachableJackettThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->probe()->probeJackett('nope', $this->query());
    }

    private function probe(): ProviderHealthProbe
    {
        return new ProviderHealthProbe(new JackettUrlBuilder('127.0.0.1', '1', 'key'));
    }

    private function configuration(Url $url): ProviderConfiguration
    {
        return new ProviderConfiguration(
            GenericProvider::class,
            new ProviderInformation('test', $url)
        );
    }

    private function query(): SearchQueryBuilder
    {
        return new SearchQueryBuilder(new SearchQuery('Inception'), new Resolution('1080p'));
    }
}
