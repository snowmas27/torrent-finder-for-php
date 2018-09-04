<?php

namespace TorrentFinder\Search;

class SearchQuery
{
	private $title;
	private $year;

	public static function movie(string $title, int $year): self
	{
		$searchQuery = new static($title);
		$searchQuery->year = $year;

		return $searchQuery;
	}

	public static function tvShowEpisode(string $tvShowName, int $seasonNumber, int $episodeNumber): self
	{
		return new self(sprintf('%s S%02dE%02d', $tvShowName, $seasonNumber, $episodeNumber));
	}

	public function __construct(string $query)
	{
		$this->title = $this->escapeUnWantedCharacters($query);
	}

	public function getQuery(): string
	{
		if (null === $this->year) {
			return $this->title;
		}
		return sprintf('%s %d', $this->title, $this->year);
	}

	private function escapeUnWantedCharacters(string $value): string
	{
		$value = str_replace(
			[':', '?', "'", '-', '.', ',', '/', '·', '[', ']', '(', ')', '!'],
			['', '', ' ', ' ', ' ', '', ' ', ' ', '', '', '', '', ''],
			$value
		);
		$value = preg_replace('/\s+/', ' ', $value);
		$value = strtolower($value);

		return ucwords(trim($value));
	}
}
