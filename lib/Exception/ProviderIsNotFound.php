<?php

namespace TorrentFinder\Exception;

use Throwable;

class ProviderIsNotFound extends \Exception
{
	public function __construct(string $providerName, Throwable $previous = null)
	{
		parent::__construct(sprintf("Provider '%s' is not found", $providerName), 0, $previous);
	}
}