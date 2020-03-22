<?php

namespace TorrentFinder\Provider;

class ProviderFactory
{
    public function buildFromProviderConfiguration(ProviderConfiguration $configuration): Provider
    {
        $className = $configuration->getClassName();

        return new $className($configuration->getInformation());
    }
}
