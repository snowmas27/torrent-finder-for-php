<?php

namespace TorrentFinder\Provider;

class ProvidersAvailable
{
    const ZOOQLE = 'zooqle';
    const LIMETORRENTS = 'limetorrents';
    const MAGNET4YOU = 'magnet4you';
    const EXTRATORRENT = 'extratorrent';
    const BTDB = 'btdb';
    const TORRENTDOWNLOAD = 'torrentdownload';
    const NYAA = 'nyaa';
    const TORRENT9 = 'torrent9';
    const SEEDPEER = 'seedpeer';
    const TORRENTGALAXY = 'torrentgalaxy';
    const TORLOCK = 'torlock';
    const PROVIDER1333X = '1333x';
    const ANIMEOSHO = 'animetosho';
    const THE_PIRATE_BAY = 'thepiratebay';
    const TORRENTZ2 = 'torrentz2';

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
            self::TORLOCK,
            self::PROVIDER1333X,
            self::ANIMEOSHO,
            self::THE_PIRATE_BAY,
            self::BTDB,
            self::TORRENTZ2,
        ];
    }
}
