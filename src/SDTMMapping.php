<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

class SDTMMapping
{
	public string $uid;
	public string $domain;
	public string $instance;
	public string $calc_text;

	public function __construct(array $payload) {
		$this->uid = $payload['uid'];
		$this->domain = $payload['domain'];
		$this->instance = $payload['instance'];
		$this->calc_text = $payload['calc_text'];
		// TODO: get substring
		// SDTM:<Class abbreviation>.<dataset name>.<variable name>
	}

}
