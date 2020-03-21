<?php

namespace App\Provider\ResultSet;

class ResultsFoundSummaryByProvider
{
    private $providerName;
    private $resultsNumber;

    public function __construct(string $providerName, int $resultsNumber)
    {
        $this->providerName = $providerName;
        $this->resultsNumber = $resultsNumber;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getResultsNumber(): int
    {
        return $this->resultsNumber;
    }
}