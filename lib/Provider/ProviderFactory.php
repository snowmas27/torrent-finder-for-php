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
				break;
			case ProvidersAvailable::LIMETORRENTS:
				return new LimeTorrents();
				break;
			case ProvidersAvailable::MAGNET4YOU:
				return new Magnet4You();
				break;
            case ProvidersAvailable::EXTRATORRENT:
                return new Extratorrent();
                break;
			case ProvidersAvailable::TORRENTDOWNLOAD:
				return new TorrentDownload();
				break;
            case ProvidersAvailable::EZTV:
                return new EzTv();
                break;
            case ProvidersAvailable::NYAA:
                return new Nyaa();
                break;
            case ProvidersAvailable::ETTV:
                return new Ettv();
                break;
		}
		
		throw new ProviderIsNotFound($name);
	}
}
