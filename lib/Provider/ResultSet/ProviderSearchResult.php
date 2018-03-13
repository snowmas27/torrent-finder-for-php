<?php

namespace TorrentFinder\Provider\ResultSet;

class ProviderSearchResult
{
	private $providerName;
	/** @var ProviderResult[] $results */
	private $results;

	public static function noResults(string $providerName): self
	{
		return new self($providerName, [], []);
	}

	public function __construct(string $providerName, array $results)
	{
		$this->providerName = $providerName;
		$this->results = $results;
	}

	public function getProviderName(): string
	{
		return $this->providerName;
	}

	/**
	 * @return ProviderResult[]
	 */
	public function getResults(): array
	{
		return $this->results;
	}

	public function countResults(): int
	{
		return count($this->results);
	}

	public function hasResults(): bool
	{
		return count($this->results) > 0;
	}
}