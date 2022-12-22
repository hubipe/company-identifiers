<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Poland extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		$id = $this->formatCompanyId($id);
		if (preg_match('/^0*$/', $id)) {
			return FALSE;
		}
		else if (preg_match('/^\d{9}$/', $id)) {
			$weights = [8, 9, 2, 3, 4, 5, 6, 7];
		}
		else if (preg_match('/^\d{14}$/', $id)) {
			if (!$this->isCompanyIdValid(substr($id, 0, 9))) {
				return FALSE;
			}
			$weights = [2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8];
		}
		else {
			return FALSE;
		}

		$sum = 0;
		$count = count($weights);
		for ($i = 0; $i < $count; $i++) {
			$sum += $id[$i] * $weights[$i];
		}
		$checkSum = $sum % 11;
		if ($checkSum === 10) {
			$checkSum = 0;
		}

		return $checkSum === (int) substr($id, -1);
	}

	public function formatCompanyId(string $id): string
	{
		return preg_replace('/\D+/', '', $id);
	}

	public function getCompanyIdentifier(string $id): string
	{
		return $this->formatCompanyId($id);
	}


	public function isCompanyVatIdValid(string $vatId): bool
	{
		$vatId = $this->formatCompanyVatId($vatId);
		if (substr($vatId, 0, 2) !== 'PL') {
			return FALSE;
		}
		return EuropeanVatValidator::getInstance()->isVatValid($vatId);
	}

	public function formatCompanyVatId(string $vatId): string
	{
		$formetted = EuropeanVatValidator::getInstance()->formatVat($vatId);
		if (preg_match('/^\d+$/', $formetted)) {
			$formetted = 'PL' . $formetted;
		}
		return $formetted;
	}


}
