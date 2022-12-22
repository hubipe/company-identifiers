<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Hungary extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		// check if input comply with defined format
		$matches = [];
		if (!preg_match('/^(?P<vatNumber>\d{8})-(?P<vatType>[1-5])-(?P<regionCode>\d{2})$/', $this->formatCompanyId($id), $matches)) {
			return FALSE;
		}

		// check if first part of the ID has the valid VAT number format
		$vatId = 'HU' . $matches['vatNumber'];
		return $this->isCompanyVatIdValid($vatId);
	}


	public function formatCompanyId(string $id): string
	{
		return preg_replace('/[^\d\-]/', '', $id);
	}

	public function getCompanyIdentifier(string $id): string
	{
		return $this->formatCompanyId($id);
	}

	public function isCompanyVatIdValid(string $vatId): bool
	{
		$vatId = $this->formatCompanyVatId($vatId);
		if (substr($vatId, 0, 2) !== 'HU') {
			return FALSE;
		}
		return EuropeanVatValidator::getInstance()->isVatValid($vatId);
	}

}
