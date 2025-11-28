<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Greece extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		$ic = $this->formatCompanyId($id);

		return preg_match('#^\d{12}$#', $ic) === 1;
	}

	public function formatCompanyId(string $id): string
	{
		return preg_replace('/\D+/', '', $id);
	}

	public function getCompanyIdentifier(string $id): string
	{
		return preg_replace('/\s+/', '', $this->formatCompanyId($id));
	}

	public function isCompanyVatIdValid(string $vatId): bool
	{
		$vatId = $this->formatCompanyVatId($vatId);
		if (substr($vatId, 0, 2) !== 'EL') {
			return FALSE;
		}
		return EuropeanVatValidator::getInstance()->isVatValid($vatId);
	}

}
