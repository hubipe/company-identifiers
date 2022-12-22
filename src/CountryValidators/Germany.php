<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Germany extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		$id = trim(strtoupper($id));
		return preg_match('/^(HRA|HRB|GNR|PR|VR)\s*\d{1,6}[A-Z]{0,5}/', $id) === 1;
	}

	public function formatCompanyId(string $id): string
	{
		$id = trim(strtoupper($id));
		$matches = [];
		if (!preg_match('/(?P<type>HRA|HRB|GNR|PR|VR)\s*(?P<num>\d{1,6}[A-Z]{0,5})/', $id, $matches)) {
			return $id;
		}
		return $matches['type'] . ' ' . $matches['num'];
	}

	public function getCompanyIdentifier(string $id): string
	{
		return preg_replace('/\s+/', '', $this->formatCompanyId($id));
	}

	public function isCompanyVatIdValid(string $vatId): bool
	{
		$vatId = $this->formatCompanyVatId($vatId);
		if (substr($vatId, 0, 2) !== 'DE') {
			return FALSE;
		}
		return EuropeanVatValidator::getInstance()->isVatValid($vatId);
	}

}
