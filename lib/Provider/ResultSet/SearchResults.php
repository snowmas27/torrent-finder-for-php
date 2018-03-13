<?php

namespace TorrentFinder\Provider\ResultSet;

class SearchResults
{
	/** @var ProviderSearchResult[] */
	private $results;
	private $summary;

	public function __construct()
	{
		$this->results = [];
		$this->summary = new SearchResultSummary([]);
	}

	public function add(ProviderSearchResult $providerSearchResults)
	{
		$this->results[] = $providerSearchResults;
		$this->updateSummary();
	}

	/**
	 * @return ProviderResult[]
	 */
	public function getResults(): array
	{
		$results = [];
		foreach ($this->results as $result) {
			foreach ($result->getResults() as $providerResult) {
				$results[] = new ProviderResult($providerResult->getProvider(), $providerResult->getTorrentMetaData(), $providerResult->getSize());
			}
		}

		return $this->sortBySeeds($results);
	}

	public function getResultsWithSeedsGreaterThan(int $seeds): array
    {
        $results = [];
        foreach ($this->results as $result) {
            foreach ($result->getResults() as $providerResult) {
                if ($providerResult->getTorrentMetaData()->getSeeds() < $seeds) {
                    continue;
                }
                $results[] = new ProviderResult($providerResult->getProvider(), $providerResult->getTorrentMetaData(), $providerResult->getSize());
            }
        }

        return $this->sortBySeeds($results);
    }

	public function getSummary(): SearchResultSummary
	{
		return $this->summary;
	}

	private function sortBySeeds(array $results): array
    {
        usort($results, function(ProviderResult $a, ProviderResult $b) {
            return $a->getTorrentMetaData()->getSeeds() < $b->getTorrentMetaData()->getSeeds();
        });

        return $results;
    }

	private function updateSummary()
	{
		$score = [];
		$bestResults = $this->getResults();
		foreach ($bestResults as $result) {
		    $providerName = $result->getProvider();
			$score[$providerName] = !isset($score[$providerName]) ? 1 : $score[$providerName] + 1;
		}

		$resultsFoundSummaryByProvider = [];
		foreach ($score as $providerName => $nbrResults) {
			$resultsFoundSummaryByProvider[] = new ResultsFoundSummaryByProvider($providerName, $nbrResults);
		}
		$this->summary = new SearchResultSummary($resultsFoundSummaryByProvider);
	}
}