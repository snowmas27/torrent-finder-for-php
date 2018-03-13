<?php

namespace TorrentFinder\Provider;

class ProvidersAvailable
{
	const ZOOQLE = 'zooqle';
	const LIMETORRENTS = 'limetorrents';
	const DEMONOID = 'demonoid';
	const IDOPE = 'idope';
	const MAGNET4YOU = 'magnet4you';
    const EXTRATORRENT = 'extratorrent';

	/**
	 * @return Provider[]
	 */
	public static function getList(): array
	{
		return [
			self::LIMETORRENTS,
			self::ZOOQLE,
			self::DEMONOID,
			self::IDOPE,
			self::MAGNET4YOU,
            self::EXTRATORRENT,
		];
	}
}