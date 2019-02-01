<?php

namespace TorrentFinder\Provider;

use TorrentFinder\Exception\ProviderIsNotFound;

class ProviderFactory
{
	public static function buildFromName(string $name): Provider
	{
		switch ($name) {
			case ProvidersAvailable::ZOOQLE:
				return new Zooqle();
			case ProvidersAvailable::LIMETORRENTS:
				return new LimeTorrents();
			case ProvidersAvailable::MAGNET4YOU:
				return new Magnet4You();
            case ProvidersAvailable::EXTRATORRENT:
                return new Extratorrent();
			case ProvidersAvailable::TORRENTDOWNLOAD:
				return new TorrentDownload();
            case ProvidersAvailable::NYAA:
                return new Nyaa();
            case ProvidersAvailable::TORRENT9:
                return new Torrent9();
            case ProvidersAvailable::SEEDPEER:
                return new SeedPeer();
            case ProvidersAvailable::TORRENTGALAXY:
                return new TorrentGalaxy();
            case ProvidersAvailable::PROVIDER1333X:
                return new Provider1337x();
            case ProvidersAvailable::ANIMEOSHO:
                return new Animetosho();
            case ProvidersAvailable::THE_PIRATE_BAY:
                return new ThePirateBay();
        }
		
		throw new ProviderIsNotFound($name);
	}
}
