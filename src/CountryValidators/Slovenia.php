<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Slovenia extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		$id = $this->formatCompanyId($id);

		return preg_match('#^\d{7}(000)?$#', $id) === 1;
	}

	public function formatCompanyId(string $id): string
	{
		return preg_replace('/\s+/', '', $id);
	}

	public function getCompanyIdentifier(string $id): string
	{
		return $this->formatCompanyId($id);
	}


	public function isCompanyVatIdValid(string $vatId): bool
	{
		$vatId = $this->formatCompanyVatId($vatId);
		if (substr($vatId, 0, 2) !== 'SI') {
			return FALSE;
		}
		return EuropeanVatValidator::getInstance()->isVatValid($vatId);
	}


}
