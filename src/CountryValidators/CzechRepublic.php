<?php

declare(strict_types=1);

namespace Hubipe\CompanyIdentifiers\CountryValidators;

class CzechRepublic implements CountryValidator
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
			$checksum += (int) $ic[$i] * (8 - $i);
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
		return str_pad((string) preg_replace('/\D+/', '', $id), 8, '0', STR_PAD_LEFT);
	}

	public function getCompanyIdentifier(string $id): string
	{
		return $this->formatCompanyId($id);
	}


	public function isCompanyVatIdValid(string $vatId): bool
	{
		$vatId = $this->formatCompanyVatId($vatId);
		if (substr($vatId, 0, 2) !== 'CZ') {
			return FALSE;
		}
		$len = strlen($vatId);
		if ($len < 10 || $len > 13) {
			return FALSE;
		}

		if (!EuropeanVatValidator::getInstance()->isVatValid($vatId)
			&& !$this->isValidPersonNumber(substr($vatId, 2))) {
			return FALSE;
		}
		return TRUE;
	}

	private function isValidPersonNumber(string $rc): bool
	{
		// be liberal in what you receive
		if (!preg_match('/^(\d\d)(\d\d)(\d\d)(\d\d\d)(\d?)$/', $rc, $matches)) {
			return FALSE;
		}

		[, $yearDigits, $monthDigits, $dayDigits, $ext, $control] = $matches;
		$year = (int) $yearDigits;
		$month = (int) $monthDigits;
		$day = (int) $dayDigits;

		if ($control === '') {
			$year += $year < 54 ? 1900 : 1800;
		}
		else {
			// kontrolní číslice
			$mod = (int) ($yearDigits . $monthDigits . $dayDigits . $ext) % 11;
			if ($mod === 10) $mod = 0;
			if ($mod !== (int)$control) {
				return FALSE;
			}

			$year += $year < 54 ? 2000 : 1900;
		}

		// k měsíci může být připočteno 20, 50 nebo 70
		if ($month > 70 && $year > 2003) {
			$month -= 70;
		}
		else if ($month > 50) {
			$month -= 50;
		}
		else if ($month > 20 && $year > 2003) {
			$month -= 20;
		}

		// kontrola data
		if (!checkdate($month, $day, $year)) {
			return FALSE;
		}

		return TRUE;
	}


}
