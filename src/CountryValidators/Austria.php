<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Austria extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		return preg_match('/^[Ff][Nn]\s*[1-9][0-9]{4,5}\s*[a-zA-Z]$/', trim($id)) === 1;
	}

	public function formatCompanyId(string $id): string
	{
		$matches = [];
		if (!preg_match('/^[Ff][Nn]\s*(?P<num>[1-9][0-9]{4,5})\s*(?P<letter>[a-zA-Z])$/', $id, $matches)) {
			return $id;
		}
		return 'FN ' . $matches['num'] . ' ' . strtolower($matches['letter']);
	}

	public function getCompanyIdentifier(string $id): string
	{
		return preg_replace('/\s+/', '', $this->formatCompanyId($id));
	}

	public function isCompanyVatIdValid(string $vatId): bool
	{
		$vatId = $this->formatCompanyVatId($vatId);
		if (substr($vatId, 0, 3) !== 'ATU') {
			return FALSE;
		}
		return EuropeanVatValidator::getInstance()->isVatValid($vatId);
	}

}
