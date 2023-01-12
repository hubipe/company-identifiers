<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Consistence\ObjectPrototype;

class Austria extends ObjectPrototype implements CountryValidator
{

	private const COMPANY_ID_WEIGHTS = [6, 4, 14, 15, 10, 1];
	private const COMPANY_ID_CHECK_CHARACTERS = 'abdfghikmpstvwxyz';

	use EuropeanVatFormatter;

	public function isCompanyIdValid(string $id): bool
	{
		$matches = [];
		if (!preg_match('/^(FN\s*)?(?P<num>[1-9][0-9]{4,5})\s*(?P<char>['. self::COMPANY_ID_CHECK_CHARACTERS .'])$/i', trim($id), $matches)) {
			return FALSE;
		}

		$num = str_pad($matches['num'], 6, '0', STR_PAD_LEFT);
		$char = strtolower($matches['char']);

		$sum = 0;
		for ($i = 0; $i < 6; $i++) {
			$sum += (int)$num[$i] * self::COMPANY_ID_WEIGHTS[$i];
		}
		$checkCharacter = self::COMPANY_ID_CHECK_CHARACTERS[$sum % 17];
		return $checkCharacter === strtolower($char);
	}

	public function formatCompanyId(string $id): string
	{
		$matches = [];
		if (!preg_match('/^(FN\s*)?(?P<num>[1-9][0-9]{4,5})\s*(?P<letter>['. self::COMPANY_ID_CHECK_CHARACTERS .'])$/i', $id, $matches)) {
			return $id;
		}
		return $matches['num'] . strtolower($matches['letter']);
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
