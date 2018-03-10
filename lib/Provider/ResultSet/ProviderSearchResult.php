<?php

namespace TorrentFinder\Provider\ResultSet;

class ProviderSearchResult
{
	private $providerName;
	/** @var ProviderResult[] $best */
	private $best;
	/** @var ProviderResult[] $all */
	private $all;

	public static function noResults(string $providerName): self
	{
		return new self($providerName, [], []);
	}

	public function __construct(string $providerName, array $best, array $all)
	{
		$this->providerName = $providerName;
		$this->best = $best;
		$this->all = $all;
	}

	public function getProviderName(): string
	{
		return $this->providerName;
	}

	/**
	 * @return ProviderResult[]
	 */
	public function getBest(): array
	{
		return $this->best;
	}

	/**
	 * @return ProviderResult[]
	 */
	public function getAll(): array
	{
		return $this->all;
	}

	public function countBest(): int
	{
		return count($this->best);
	}

	public function countAll(): int
	{
		return count($this->all);
	}

	public function hasBestResults(): bool
	{
		return count($this->best) > 0;
	}

	public function hasAnyResults(): bool
	{
		return count($this->all) > 0;
	}
}