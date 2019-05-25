# torrent-finder-for-php
Torrent finder for PHP is a movie and TvShow search engine based on various torrent sites.
* 1337X
* Animetosho
* Btdb
* Extratorrent
* EZTV
* Lime torrents
* Magnet4You
* Nyaa
* SeedPeer
* T411
* The Pirate Bay
* Torlock
* Torrent4You
* Torrent9
* Torrentdownload
* Torrent galaxy
* Torrentz2
* Zooqle

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
use TorrentFinder\Search\SearchQuery;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\Search\SearchOnProviders;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\Provider\ProvidersAvailable;

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
use TorrentFinder\Search\SearchQuery;
use TorrentFinder\Search\SearchQueryBuilder;
use TorrentFinder\Search\SearchOnProviders;
use TorrentFinder\VideoSettings\Resolution;
use TorrentFinder\Provider\ProvidersAvailable;

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
