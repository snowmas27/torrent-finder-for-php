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
			case ProvidersAvailable::DEMONOID:
				return new Demonoid();
				break;
			case ProvidersAvailable::LIMETORRENTS:
				return new LimeTorrents();
				break;
			case ProvidersAvailable::IDOPE:
				return new Idope();
				break;
			case ProvidersAvailable::MAGNET4YOU:
				return new Magnet4You();
				break;
		}
		throw new ProviderIsNotFound($name);
	}
}