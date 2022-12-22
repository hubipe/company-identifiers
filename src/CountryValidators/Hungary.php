<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Hungary extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		return preg_match('/^(\d{2})(\s*|-)(\d{2})(\s*|-)\d{6}$/', trim($id)) === 1;
	}

	public function formatCompanyId(string $id): string
	{
		$matches = [];
		if (!preg_match('/^(?P<court>\d{2})(\s*|-)(?P<legalForm>\d{2})(\s*|-)(?P<num>\d{6})$/', $id, $matches)) {
			return $id;
		}
		return $matches['court'] . '-' . $matches['legalForm'] . '-' . $matches['num'];
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
