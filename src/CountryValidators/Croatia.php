<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Croatia extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		$id = $this->formatCompanyId($id);

		if (preg_match('/^0*$/', $id)) {
			return FALSE;
		}
		else if (!preg_match('/^\d{11}$/', $id)) {
			return FALSE;
		}

		$product = 10;
		$sum = 0;
		$checkDigit = 0;

		for ($i = 0; $i < 10; ++$i) {
			// Extract the next digit and implement the algorithm
			$sum = ((int) $id[$i] + $product) % 10;
			if (0 === $sum) {
				$sum = 10;
			}

			$product = (2 * $sum) % 11;
		}

		// Now check that we have the right check digit
		return 1 === ($product + (int) $id[10]) % 10;
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
		if (substr($vatId, 0, 2) !== 'HR') {
			return FALSE;
		}
		return EuropeanVatValidator::getInstance()->isVatValid($vatId);
	}


}
