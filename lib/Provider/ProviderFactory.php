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
            case ProvidersAvailable::TORLOCK:
                return new Torlock();
            case ProvidersAvailable::BTDB:
                return new Btdb();
            case ProvidersAvailable::TORRENTZ2:
                return new Torrentz2();
            case ProvidersAvailable::T411:
                return new T411();
            case ProvidersAvailable::TORRENT4YOU:
                return new Torrent4You();
            case ProvidersAvailable::EZTV:
                return new Eztv();
            case ProvidersAvailable::RARBG:
                return new Rarbg();
        }
        
        throw new ProviderIsNotFound($name);
    }
}
