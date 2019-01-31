<?php

namespace TorrentFinder\Provider;

class ProvidersAvailable
{
	const ZOOQLE = 'zooqle';
	const LIMETORRENTS = 'limetorrents';
	const MAGNET4YOU = 'magnet4you';
    const EXTRATORRENT = 'extratorrent';
	const TORRENTDOWNLOAD = 'torrentdownload';
    const NYAA = 'nyaa';
    const TORRENT9 = 'torrent9';
    const SEEDPEER = 'seedpeer';
    const TORRENTGALAXY = 'torrentgalaxy';
    const PROVIDER1333X = '1333x';
    const ANIMEOSHO = 'animetosho';

	/**
	 * @return Provider[]
	 */
	public static function getList(): array
	{
		return [
			self::LIMETORRENTS,
			self::ZOOQLE,
			self::MAGNET4YOU,
            self::EXTRATORRENT,
			self::TORRENTDOWNLOAD,
            self::NYAA,
            self::TORRENT9,
            self::SEEDPEER,
            self::TORRENTGALAXY,
            self::PROVIDER1333X,
            self::ANIMEOSHO,
		];
	}
}
