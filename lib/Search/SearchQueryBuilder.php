<?php

namespace TorrentFinder\Search;

use Matriphe\ISO639\ISO639;
use TorrentFinder\VideoSettings\Resolution;

class SearchQueryBuilder
{
	private $query;
	private $resolution;
	private $searchKeywords;
	private $version;

	public function __construct(SearchQuery $query, Resolution $resolution)
	{
		$this->query = $query->getQuery();
		$this->resolution = $resolution->getValue();
		$this->searchKeywords = trim(sprintf('%s %s', $this->query, $resolution->getValueForSearch()));
	}

	public function getQuery(): string
	{
		return $this->query;
	}

	public function getFormat(): Resolution
	{
		return new Resolution($this->resolution);
	}

	public function getSearchKeywords(): string
	{
		return (null !== $this->version) ? sprintf('%s %s', $this->searchKeywords, $this->version) : $this->searchKeywords;
	}

	public function withAudioLanguageVersion(string $languageIsoCode)
	{
		$iso = new ISO639();
		$this->version = $iso->languageByCode1($languageIsoCode);
	}

	public function urlize(string $char = '+'): string
	{
		return str_replace(' ', $char, $this->getSearchKeywords());
	}

	public function urlEncode(): string
	{
		return urlencode($this->getSearchKeywords());
	}
	public function rawUrlEncode(): string
	{
		return rawurlencode($this->getSearchKeywords());
	}
}