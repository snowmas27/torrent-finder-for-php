<?php

namespace TorrentFinder\Provider;

class ProvidersAvailable
{
	const ZOOQLE = 'zooqle';
	const LIMETORRENTS = 'limetorrents';
	const MAGNET4YOU = 'magnet4you';
    const EXTRATORRENT = 'extratorrent';
	const TORRENTDOWNLOAD = 'torrentdownload';
	const DEMONOID = 'demonoid';

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
//			self::DEMONOID,
		];
	}
}
