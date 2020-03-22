<?php

namespace TorrentFinder\Provider;

use TorrentFinder\Utils\Url;

class ProvidersConfiguration
{
    private $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @return ProviderConfiguration[]
     */
    public function getConfiguration(): array
    {
        $list = [];
        foreach ($this->providers as $provider) {
            $list[] = new ProviderConfiguration(
                $provider['class'],
                new ProviderInformation($provider['name'], Url::fromString($provider['searchUrl']))
            );
        }

        return $list;
    }

    public function getProvidersName(): array
    {
        $list = [];

        foreach ($this->providers as $provider) {
            $list[] = $provider['name'];
        }

        return $list;
    }

    public function hasProvider(string $name): bool
    {
        return in_array($name, $this->getProvidersName(), true);
    }
}