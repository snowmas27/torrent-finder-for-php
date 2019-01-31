# torrent-finder-for-php
Torrent finder for PHP is a movie and TvShow search engine based on various torrent sites.
* Zooqle
* Lime torrents
* Magnet4You
* Extratorrent
* Torrentdownload
* Nyaa
* Torrent9

New providers will be added later.

## Installation

Installation is really simple when using [Composer](https://getcomposer.org/):

```
composer require snowmas27/torrent-finder-for-php
```

## Usage

The following code is an example of how to use the library

### Movie

```
$query = SearchQuery::movie('Wonder Woman', 2017);

$searchKeywords = new SearchQueryBuilder($query, Resolution::fullHd());

// Searching on all providers
$results = SearchOnProviders::all()->search([
    $searchKeywords
]);

// or specifics ones
$providers = [
	ProvidersAvailable::ZOOQLE,
	ProvidersAvailable::MAGNET4YOU,
];
$results = SearchOnProviders::specificProviders($providers)->search([
    $searchKeywords
]);
// List torrents information found
$results->getResults();

```

### TvShow episode
```
// (title, season, episode)
$query = SearchQuery::tvShowEpisode('Game Of Thrones', 2, 6);

$searchKeywords = new SearchQueryBuilder($query, Resolution::fullHd());

// Searching on all providers
$results = SearchOnProviders::all()->search([
    $searchKeywords
]);

// or specifics ones
$providers = [
	ProvidersAvailable::ZOOQLE,
	ProvidersAvailable::MAGNET4YOU,
];
$results = SearchOnProviders::specificProviders($providers)->search([
    $searchKeywords
]);
// List torrents information found
$results->getResults();
```

Torrents found are automatically sorted by seeds with additional information. 
