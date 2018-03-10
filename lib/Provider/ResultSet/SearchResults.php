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
	public function getBestResults(): array
	{
		$best = [];
		foreach ($this->results as $result) {
			foreach ($result->getBest() as $providerBest) {
				$best[] = new ProviderResult($providerBest->getProvider(), $providerBest->getTorrentMetaData(), $providerBest->getSize());
			}
		}
		usort($best, function(ProviderResult $a, ProviderResult $b) {
			return $a->getTorrentMetaData()->getSeeds() < $b->getTorrentMetaData()->getSeeds();
		});

		return $best;
	}

	/**
	 * @return ProviderResult[]
	 */
	public function getAllResults(): array
	{
		$all = [];
		foreach ($this->results as $result) {
			foreach ($result->getAll() as $providerBest) {
				$all[] = new ProviderResult($providerBest->getProvider(), $providerBest->getTorrentMetaData(), $providerBest->getSize());
			}
		}
		usort($all, function(ProviderResult $a, ProviderResult $b) {
			return $a->getTorrentMetaData()->getSeeds() < $b->getTorrentMetaData()->getSeeds();
		});

		return $all;
	}

	public function getSummary(): SearchResultSummary
	{
		return $this->summary;
	}

	private function updateSummary()
	{
		$score = [];
		$bestResults = $this->getBestResults();
		foreach ($bestResults as $result) {
			$score[$result->getProvider()] = !isset($score[$result->getProvider()]) ? 1 : $score[$result->getProvider()] + 1;
		}

		$resultsFoundSummaryByProvider = [];
		foreach ($score as $providerName => $nbrResults) {
			$resultsFoundSummaryByProvider[] = new ResultsFoundSummaryByProvider($providerName, $nbrResults);
		}
		$this->summary = new SearchResultSummary($resultsFoundSummaryByProvider);
	}
}