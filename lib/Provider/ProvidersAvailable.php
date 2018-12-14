<?php

namespace TorrentFinder\Provider;

class ProvidersAvailable
{
	const ZOOQLE = 'zooqle';
	const LIMETORRENTS = 'limetorrents';
	const MAGNET4YOU = 'magnet4you';
    const EXTRATORRENT = 'extratorrent';
	const TORRENTDOWNLOAD = 'torrentdownload';
    const ETTV = 'ettv';
    const NYAA = 'nyaa';
    const BTDB = 'btdb';
    const SEEDPEER = 'seedpeer';
    const TORRENTGALAXY = 'torrentgalaxy';

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
            self::ETTV,
            self::NYAA,
            self::BTDB,
            self::SEEDPEER,
            self::TORRENTGALAXY,
		];
	}
}
