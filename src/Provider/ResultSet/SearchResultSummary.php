<?php

namespace App\Provider\ResultSet;

class SearchResultSummary
{
    private $summary = [];
    private $total = 0;

    /**
     * @param ResultsFoundSummaryByProvider[] $summary
     */
    public function __construct(array $summary)
    {
        $this->summary = $summary;
        $this->updateTotal();
    }

    /**
     * @return ResultsFoundSummaryByProvider[]
     */
    public function getSummary(): array
    {
        return $this->summary;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    private function updateTotal()
    {
        foreach ($this->summary as $summaryByProvider) {
            $this->total += $summaryByProvider->getResultsNumber();
        }
    }
}