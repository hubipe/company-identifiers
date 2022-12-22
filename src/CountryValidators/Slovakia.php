<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;
use Nette\Utils\Strings;

class Slovakia extends ObjectPrototype implements CountryValidator
{

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		$ic = $this->formatCompanyId($id);

		if (!preg_match('#^\d{8}$#', $ic)) {
			return FALSE;
		}

		// checksum
		$checksum = 0;
		for ($i = 0; $i < 7; $i++) {
			$checksum += $ic[$i] * (8 - $i);
		}

		$checksum = $checksum % 11;
		if ($checksum === 0) {
			$controlNum = 1;
		}
		else if ($checksum === 1) {
			$controlNum = 0;
		}
		else {
			$controlNum = 11 - $checksum;
		}

		return (int)$ic[7] === $controlNum;
	}

	public function formatCompanyId(string $id): string
	{
		return Strings::padLeft(preg_replace('/\D+/', '', $id), 8, '0');
	}

	public function getCompanyIdentifier(string $id): string
	{
		return $this->formatCompanyId($id);
	}


	public function isCompanyVatIdValid(string $vatId): bool
	{
		$vatId = $this->formatCompanyVatId($vatId);
		if (substr($vatId, 0, 2) !== 'SK') {
			return FALSE;
		}
		return EuropeanVatValidator::getInstance()->isVatValid($vatId);
	}


}
