<?php

namespace TorrentFinder\Provider;

class ProviderConfiguration
{
    private $className;
    private $information;

    public function __construct(string $className, ProviderInformation $information)
    {
        $this->className = $className;
        $this->information = $information;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getInformation(): ProviderInformation
    {
        return $this->information;
    }
}