<?php

/*
 * VAT number format and check digit validation.
 *
 * The schemes and the check digit algorithms were taken over from
 * antalaron/vat-number-validator v1.2.1 (https://github.com/antalaron/vat-number-validator),
 * which is in turn based on http://www.braemoor.co.uk/software/vat.shtml.
 * They were rewritten into typed code without the Symfony Validator dependency;
 * the calculations are unchanged unless stated otherwise in the comments.
 *
 * Original code: Copyright (c) 2017 Antal Áron <antalaron@antalaron.hu>, MIT License:
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace Hubipe\CompanyIdentifiers\Vat;

/**
 * Validates the format and the check digits of VAT numbers (with the country prefix, e.g. CZ12345679).
 *
 * Supported prefixes: AT, BE, BG, CHE (Switzerland), CY, CZ, DE, DK, EE, EL (Greece), ES, EU, FI, FR,
 * GB, HR, HU, IE, IT, LT, LU, LV, MT, NL, NO, PL, PT, RO, RS, RU, SE, SI, SK.
 *
 * It does not verify that the VAT number is actually registered.
 */
final class VatNumberChecker
{

	/**
	 * VAT number schemes in the order they are tried. Regex group 1 is the country code which selects
	 * the check digit algorithm, group 2 is the number passed to it.
	 *
	 * Only the first matching scheme is used: if its check fails, the following variants of the same
	 * country are not tried.
	 *
	 * Schemes without "+" in the comment have no check digit validation.
	 */
	private const SCHEMES = [
		'/^(AT)U(\d{8})$/',                         // + Austria
		'/^(BE)(0?\d{9})$/',                        // + Belgium
		'/^(BG)(\d{9,10})$/',                       // + Bulgaria
		'/^(CHE)(\d{9})(MWST|TVA|IVA)?$/',          // + Switzerland (not EU)
		'/^(CY)([0-59]\d{7}[A-Z])$/',               // + Cyprus
		'/^(CZ)(\d{8,10})(\d{3})?$/',               // + Czech Republic
		'/^(DE)([1-9]\d{8})$/',                     // + Germany
		'/^(DK)(\d{8})$/',                          // + Denmark
		'/^(EE)(10\d{7})$/',                        // + Estonia
		'/^(EL)(\d{9})$/',                          // + Greece
		'/^(ES)([A-Z]\d{8})$/',                     // + Spain (National juridical entities)
		'/^(ES)([A-HN-SW]\d{7}[A-J])$/',            // + Spain (Other juridical entities)
		'/^(ES)([0-9YZ]\d{7}[A-Z])$/',              // + Spain (Personal entities type 1)
		'/^(ES)([KLMX]\d{7}[A-Z])$/',               // + Spain (Personal entities type 2)
		'/^(EU)(\d{9})$/',                          // + EU-type
		'/^(FI)(\d{8})$/',                          // + Finland
		'/^(FR)(\d{11})$/',                         // + France (1)
		'/^(FR)([A-HJ-NP-Z]\d{10})$/',              // France (2)
		'/^(FR)(\d[A-HJ-NP-Z]\d{9})$/',             // France (3)
		'/^(FR)([A-HJ-NP-Z]{2}\d{9})$/',            // France (4)
		'/^(GB)(\d{9})$/',                          // + UK (Standard)
		'/^(GB)(\d{12})$/',                         // + UK (Branches)
		'/^(GB)(GD\d{3})$/',                        // + UK (Government)
		'/^(GB)(HA\d{3})$/',                        // + UK (Health authority)
		'/^(HR)(\d{11})$/',                         // + Croatia
		'/^(HU)(\d{8})$/',                          // + Hungary
		'/^(IE)(\d{7}[A-W])$/',                     // + Ireland (1)
		'/^(IE)([7-9][A-Z\*\+)]\d{5}[A-W])$/',      // + Ireland (2)
		'/^(IE)(\d{7}[A-W][AH])$/',                 // + Ireland (3)
		'/^(IT)(\d{11})$/',                         // + Italy
		'/^(LT)(\d{9}|\d{12})$/',                   // + Lithuania
		'/^(LV)(\d{11})$/',                         // + Latvia
		'/^(LU)(\d{8})$/',                          // + Luxembourg
		'/^(MT)([1-9]\d{7})$/',                     // + Malta
		'/^(NL)(\d{9})B\d{2}$/',                    // + Netherlands
		'/^(NO)(\d{9})$/',                          // + Norway (not EU)
		'/^(PL)(\d{10})$/',                         // + Poland
		'/^(PT)(\d{9})$/',                          // + Portugal
		'/^(RO)([1-9]\d{1,9})$/',                   // + Romania
		'/^(RS)(\d{9})$/',                          // + Serbia (not EU)
		'/^(RU)(\d{10}|\d{12})$/',                  // + Russia (not EU)
		'/^(SE)(\d{10}01)$/',                       // + Sweden
		'/^(SI)([1-9]\d{7})$/',                     // + Slovenia
		'/^(SK)([1-9]\d[2346-9]\d{7})$/',           // + Slovakia
	];

	/**
	 * Returns TRUE when the VAT number matches a known scheme and its check digits are correct.
	 *
	 * The number is normalized first (see normalize()). An empty number (also after normalization) is
	 * considered valid, as it was in antalaron/vat-number-validator.
	 */
	public function isValid(string $vat): bool
	{
		$vat = self::normalize($vat);
		if ($vat === '') {
			return TRUE;
		}

		foreach (self::SCHEMES as $scheme) {
			if (preg_match($scheme, $vat, $match) === 1) {
				return $this->check($match[1], $match[2], $vat);
			}
		}

		return FALSE;
	}

	/**
	 * Uppercases the VAT number and removes whitespace, dashes and dots.
	 */
	public static function normalize(string $vat): string
	{
		return (string) preg_replace('/(\s|-|\.)+/', '', strtoupper($vat));
	}

	/**
	 * @param string $countryCode regex group 1 of the matched scheme
	 * @param string $number regex group 2 of the matched scheme
	 * @param string $vat whole normalized VAT number
	 */
	private function check(string $countryCode, string $number, string $vat): bool
	{
		return match ($countryCode) {
			'AT' => $this->checkAT($number),
			'BE' => $this->checkBE($number),
			'BG' => $this->checkBG($number),
			'CHE' => $this->checkCHE($number),
			'CY' => $this->checkCY($number),
			'CZ' => $this->checkCZ($number),
			'DE' => $this->checkDE($number),
			'DK' => $this->checkDK($number),
			'EE' => $this->checkEE($number),
			'EL' => $this->checkEL($number),
			'ES' => $this->checkES($number),
			'EU' => TRUE, // nothing is known about EU numbers apart from the format
			'FI' => $this->checkFI($number),
			'FR' => $this->checkFR($number),
			'GB' => $this->checkGB($number),
			'HR' => $this->checkHR($number),
			'HU' => $this->checkHU($number),
			'IE' => $this->checkIE($number),
			'IT' => $this->checkIT($number),
			'LT' => $this->checkLT($number),
			'LU' => $this->checkLU($number),
			'LV' => $this->checkLV($number),
			'MT' => $this->checkMT($number),
			'NL' => $this->checkNL($number, $vat),
			'NO' => $this->checkNO($number),
			'PL' => $this->checkPL($number),
			'PT' => $this->checkPT($number),
			'RO' => $this->checkRO($number),
			'RS' => $this->checkRS($number),
			'RU' => $this->checkRU($number),
			'SE' => $this->checkSE($number),
			'SI' => $this->checkSI($number),
			'SK' => $this->checkSK($number),
			default => FALSE,
		};
	}

	/**
	 * Sum of digits of a number lower than 100 (used by the Luhn-like algorithms).
	 */
	private static function digitSum(int $value): int
	{
		return intdiv($value, 10) + $value % 10;
	}

	private function checkAT(string $number): bool
	{
		$multipliers = [1, 2, 1, 2, 1, 2, 1];
		$total = 0;
		for ($i = 0; $i < 7; $i++) {
			$total += self::digitSum((int) $number[$i] * $multipliers[$i]);
		}

		$total = 10 - ($total + 4) % 10;
		if ($total === 10) {
			$total = 0;
		}

		return $total === (int) $number[7];
	}

	private function checkBE(string $number): bool
	{
		// Nine digit numbers have a 0 inserted at the front.
		if (strlen($number) === 9) {
			$number = '0' . $number;
		}

		if ($number[1] === '0') {
			return FALSE;
		}

		// Modulus 97 check on last nine digits
		return 97 - (int) substr($number, 0, 8) % 97 === (int) substr($number, 8, 2);
	}

	private function checkBG(string $number): bool
	{
		// Legal entities (9 digits)
		if (strlen($number) === 9) {
			$total = 0;
			for ($i = 0; $i < 8; $i++) {
				$total += (int) $number[$i] * ($i + 1);
			}

			$total = $total % 11;
			if ($total !== 10) {
				return $total === (int) $number[8];
			}

			// Modulus 10 with the first multipliers: calculate again with different multipliers.
			$total = 0;
			for ($i = 0; $i < 8; $i++) {
				$total += (int) $number[$i] * ($i + 3);
			}

			$total = $total % 11;
			if ($total === 10) {
				$total = 0;
			}

			return $total === (int) $number[8];
		}

		// Physical person
		if (preg_match('/^\d\d[0-5]\d[0-3]\d\d{4}$/', $number) === 1) {
			$month = (int) substr($number, 2, 2);
			if (($month > 0 && $month < 13) || ($month > 20 && $month < 33) || ($month > 40 && $month < 53)) {
				$multipliers = [2, 4, 8, 5, 10, 9, 7, 3, 6];
				$total = 0;
				for ($i = 0; $i < 9; $i++) {
					$total += (int) $number[$i] * $multipliers[$i];
				}

				$total = $total % 11;
				if ($total === 10) {
					$total = 0;
				}

				if ((int) $number[9] === $total) {
					return TRUE;
				}
			}
		}

		// Foreigner
		$multipliers = [21, 19, 17, 13, 11, 9, 7, 3, 1];
		$total = 0;
		for ($i = 0; $i < 9; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		if ((int) $number[9] === $total % 10) {
			return TRUE;
		}

		// Miscellaneous VAT number
		$multipliers = [4, 3, 2, 7, 6, 5, 4, 3, 2];
		$total = 0;
		for ($i = 0; $i < 9; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = 11 - $total % 11;
		if ($total === 10) {
			return FALSE;
		}
		if ($total === 11) {
			$total = 0;
		}

		return $total === (int) $number[9];
	}

	private function checkCHE(string $number): bool
	{
		$multipliers = [5, 4, 3, 2, 7, 6, 5, 4];
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = 11 - $total % 11;
		if ($total === 10) {
			return FALSE;
		}
		if ($total === 11) {
			$total = 0;
		}

		return $total === (int) $number[8];
	}

	private function checkCY(string $number): bool
	{
		// Not allowed to start with '12'
		if (substr($number, 0, 2) === '12') {
			return FALSE;
		}

		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$digit = (int) $number[$i];
			if ($i % 2 === 0) {
				$digit = match ($digit) {
					0 => 1,
					1 => 0,
					2 => 5,
					3 => 7,
					4 => 9,
					default => 2 * $digit + 3,
				};
			}
			$total += $digit;
		}

		// Check digit is modulus 26 translated to a letter.
		return $number[8] === chr($total % 26 + 65);
	}

	private function checkCZ(string $number): bool
	{
		$multipliers = [8, 7, 6, 5, 4, 3, 2];

		// Legal entities
		if (preg_match('/^\d{8}$/', $number) === 1) {
			$total = 0;
			for ($i = 0; $i < 7; $i++) {
				$total += (int) $number[$i] * $multipliers[$i];
			}

			$total = 11 - $total % 11;
			if ($total === 10) {
				$total = 0;
			}
			elseif ($total === 11) {
				$total = 1;
			}

			return (int) $number[7] === $total;
		}

		// Individuals type 1 (Standard) - 9 digits without check digit
		if (preg_match('/^[0-5][0-9][0|1|5|6][0-9][0-3][0-9]\d{3}$/', $number) === 1) {
			return (int) substr($number, 0, 2) <= 62;
		}

		// Individuals type 2 (Special Cases) - 9 digits including check digit
		if (preg_match('/^6\d{8}$/', $number) === 1) {
			$total = 0;
			for ($i = 0; $i < 7; $i++) {
				$total += (int) $number[$i + 1] * $multipliers[$i];
			}

			// Pointer into the lookup table: distance to the next multiple of 11 (11 if already a multiple).
			$pointer = 11 - $total % 11;
			$lookup = [8, 7, 6, 5, 4, 3, 2, 1, 0, 9, 8];

			return $lookup[$pointer - 1] === (int) $number[8];
		}

		// Individuals type 3 - 10 digits
		if (preg_match('/^\d{2}[0-3|5-8][0-9][0-3][0-9]\d{4}$/', $number) === 1) {
			$total = (int) substr($number, 0, 2)
				+ (int) substr($number, 2, 2)
				+ (int) substr($number, 4, 2)
				+ (int) substr($number, 5, 2)
				+ (int) substr($number, 6);

			if ($total % 11 === 0 && (int) $number % 11 === 0) {
				return TRUE;
			}

			return $this->isValidCzechBirthNumber($number);
		}

		return FALSE;
	}

	/**
	 * Czech birth number (rodné číslo) used as VAT number of individuals.
	 */
	private function isValidCzechBirthNumber(string $rc): bool
	{
		if (preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches) !== 1) {
			return FALSE;
		}

		[, $year, $month, $day, $ext, $control] = $matches;
		$year = (int) $year;
		$month = (int) $month;
		$day = (int) $day;

		if ($control === '') {
			$year += $year < 54 ? 1900 : 1800;
		}
		else {
			// check digit
			$mod = (int) ($matches[1] . $matches[2] . $matches[3] . $ext) % 11;
			if ($mod === 10) {
				$mod = 0;
			}
			if ($mod !== (int) $control) {
				return FALSE;
			}

			$year += $year < 54 ? 2000 : 1900;
		}

		// 20, 50 or 70 can be added to the month
		if ($month > 70 && $year > 2003) {
			$month -= 70;
		}
		elseif ($month > 50) {
			$month -= 50;
		}
		elseif ($month > 20 && $year > 2003) {
			$month -= 20;
		}

		return checkdate($month, $day, $year);
	}

	private function checkDE(string $number): bool
	{
		$product = 10;
		for ($i = 0; $i < 8; $i++) {
			$sum = ((int) $number[$i] + $product) % 10;
			if ($sum === 0) {
				$sum = 10;
			}

			$product = (2 * $sum) % 11;
		}

		$checkDigit = $product === 1 ? 0 : 11 - $product;

		// Compared with the last two characters of the number (group 2 has 9 digits, so it is the last one).
		return (int) substr($number, 8, 2) === $checkDigit;
	}

	private function checkDK(string $number): bool
	{
		$multipliers = [2, 7, 6, 5, 4, 3, 2, 1];
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		return $total % 11 === 0;
	}

	private function checkEE(string $number): bool
	{
		$multipliers = [3, 7, 1, 3, 7, 1, 3, 7];
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = 10 - $total % 10;
		if ($total === 10) {
			$total = 0;
		}

		return (int) $number[8] === $total;
	}

	private function checkEL(string $number): bool
	{
		// Eight character numbers should be prefixed with an 0.
		if (strlen($number) === 8) {
			$number = '0' . $number;
		}

		$multipliers = [256, 128, 64, 32, 16, 8, 4, 2];
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = $total % 11;
		if ($total > 9) {
			$total = 0;
		}

		return (int) $number[8] === $total;
	}

	private function checkES(string $number): bool
	{
		$multipliers = [2, 1, 2, 1, 2, 1, 2];

		// National juridical entities
		if (preg_match('/^[A-H|J|U|V]\d{8}$/', $number) === 1) {
			$total = 0;
			for ($i = 0; $i < 7; $i++) {
				$total += self::digitSum((int) $number[$i + 1] * $multipliers[$i]);
			}

			$total = 10 - $total % 10;
			if ($total === 10) {
				$total = 0;
			}

			return (int) $number[8] === $total;
		}

		// Juridical entities other than national ones
		if (preg_match('/^[A-H|N-S|W]\d{7}[A-J]$/', $number) === 1) {
			$total = 0;
			for ($i = 0; $i < 7; $i++) {
				$total += self::digitSum((int) $number[$i + 1] * $multipliers[$i]);
			}

			$total = 10 - $total % 10;

			return $number[8] === chr($total + 64);
		}

		$letters = 'TRWAGMYFPDXBNJZSQVHLCKE';

		// Personal number (NIF) starting with a digit, Y or Z
		if (preg_match('/^[0-9|Y|Z]\d{7}[A-Z]$/', $number) === 1) {
			if ($number[0] === 'Y') {
				$number = str_replace('Y', '1', $number);
			}
			elseif ($number[0] === 'Z') {
				$number = str_replace('Z', '2', $number);
			}

			return $number[8] === $letters[(int) substr($number, 0, 8) % 23];
		}

		// Personal number (NIF) starting with K, L, M or X ('/^[K|L|M|X]\d{7}[A-Z]$/'):
		// antalaron/vat-number-validator compared against an undefined variable here, so these numbers
		// were never valid. Kept as is to preserve the results.
		return FALSE;
	}

	private function checkFI(string $number): bool
	{
		$multipliers = [7, 9, 10, 5, 8, 4, 2];
		$total = 0;
		for ($i = 0; $i < 7; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = 11 - $total % 11;
		if ($total > 9) {
			$total = 0;
		}

		return (int) $number[7] === $total;
	}

	private function checkFR(string $number): bool
	{
		// Only the numeric format has a known check digit algorithm.
		if (preg_match('/^\d{11}$/', $number) !== 1) {
			return TRUE;
		}

		$total = (100 * (int) substr($number, -9) + 12) % 97;

		return (int) substr($number, 0, 2) === $total;
	}

	private function checkGB(string $number): bool
	{
		// Government departments
		if (substr($number, 0, 2) === 'GD') {
			return (int) substr($number, 2, 5) < 500;
		}

		// Health authorities
		if (substr($number, 0, 2) === 'HA') {
			return (int) substr($number, 2, 5) > 499;
		}

		// Standard and commercial numbers; 0 VAT numbers disallowed
		if ((int) $number === 0) {
			return FALSE;
		}

		$no = (int) substr($number, 0, 7);

		$multipliers = [8, 7, 6, 5, 4, 3, 2];
		$total = 0;
		for ($i = 0; $i < 7; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		// Old numbers use a simple 97 modulus, new numbers an adaptation of that (less 55).
		// Check digits: subtract 97 from the total until it is not positive and take the absolute value.
		$checkDigits = $total;
		while ($checkDigits > 0) {
			$checkDigits -= 97;
		}
		$checkDigits = abs($checkDigits);

		$given = (int) substr($number, 7, 2);
		if ($given === $checkDigits
			&& $no < 9990001
			&& ($no < 100000 || $no > 999999)
			&& ($no < 9490001 || $no > 9700000)
		) {
			return TRUE;
		}

		// New method: subtract 55 from the check digits if possible, else add 42.
		if ($checkDigits >= 55) {
			$checkDigits -= 55;
		}
		else {
			$checkDigits += 42;
		}

		return $given === $checkDigits && $no > 1000000;
	}

	/**
	 * ISO 7064, MOD 11-10
	 */
	private function checkHR(string $number): bool
	{
		$product = 10;
		for ($i = 0; $i < 10; $i++) {
			$sum = ((int) $number[$i] + $product) % 10;
			if ($sum === 0) {
				$sum = 10;
			}

			$product = (2 * $sum) % 11;
		}

		return ($product + (int) $number[10]) % 10 === 1;
	}

	private function checkHU(string $number): bool
	{
		$multipliers = [9, 7, 3, 1, 9, 7, 3];
		$total = 0;
		for ($i = 0; $i < 7; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = 10 - $total % 10;
		if ($total === 10) {
			$total = 0;
		}

		return $total === (int) $number[7];
	}

	private function checkIE(string $number): bool
	{
		// Type 2 format is converted to the new one before the validation.
		if (preg_match('/^\d[A-Z\*\+]/', $number) === 1) {
			$number = '0' . substr($number, 2, 5) . substr($number, 0, 1) . substr($number, 7, 1);
		}

		$multipliers = [8, 7, 6, 5, 4, 3, 2];
		$total = 0;
		for ($i = 0; $i < 7; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		// Type 3 includes the trailing A (1 * 9) or H (8 * 9) in the calculation.
		if (preg_match('/^\d{7}[A-Z][AH]$/', $number) === 1) {
			$total += $number[8] === 'H' ? 72 : 9;
		}

		// Check digit is modulus 23 translated to a letter.
		$total = $total % 23;
		$checkChar = $total === 0 ? 'W' : chr($total + 64);

		return $number[7] === $checkChar;
	}

	private function checkIT(string $number): bool
	{
		if (substr($number, 0, 7) === '0000000') {
			return FALSE;
		}

		// The last three digits are the issuing office, and cannot exceed 201, unless 999 or 888.
		$office = (int) substr($number, 7, 3);
		if ($office < 1 || ($office > 201 && $office !== 999 && $office !== 888)) {
			return FALSE;
		}

		$multipliers = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2];
		$total = 0;
		for ($i = 0; $i < 10; $i++) {
			$total += self::digitSum((int) $number[$i] * $multipliers[$i]);
		}

		$total = 10 - $total % 10;
		if ($total > 9) {
			$total = 0;
		}

		return (int) $number[10] === $total;
	}

	private function checkLT(string $number): bool
	{
		// 9 character VAT numbers are for legal persons
		if (strlen($number) === 9) {
			// 8th character must be one
			if ($number[7] !== '1') {
				return FALSE;
			}

			$total = 0;
			for ($i = 0; $i < 8; $i++) {
				$total += (int) $number[$i] * ($i + 1);
			}

			// Can have a double check digit calculation
			if ($total % 11 === 10) {
				$multipliers = [3, 4, 5, 6, 7, 8, 9, 1];
				$total = 0;
				for ($i = 0; $i < 8; $i++) {
					$total += (int) $number[$i] * $multipliers[$i];
				}
			}

			$total = $total % 11;
			if ($total === 10) {
				$total = 0;
			}

			return (int) $number[8] === $total;
		}

		// 12 character VAT numbers are for temporarily registered taxpayers; 11th character must be one
		if ($number[10] !== '1') {
			return FALSE;
		}

		$multipliers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2];
		$total = 0;
		for ($i = 0; $i < 11; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		// Can have a double check digit calculation
		if ($total % 11 === 10) {
			$multipliers = [3, 4, 5, 6, 7, 8, 9, 1, 2, 3, 4];
			$total = 0;
			for ($i = 0; $i < 11; $i++) {
				$total += (int) $number[$i] * $multipliers[$i];
			}
		}

		$total = $total % 11;
		if ($total === 10) {
			$total = 0;
		}

		return (int) $number[11] === $total;
	}

	private function checkLU(string $number): bool
	{
		return (int) substr($number, 0, 6) % 89 === (int) substr($number, 6, 2);
	}

	private function checkLV(string $number): bool
	{
		// Natural persons: the first six digits must look like a DDMMYY date.
		if (preg_match('/^[0-3]/', $number) === 1) {
			return preg_match('/^[0-3][0-9][0-1][0-9]/', $number) === 1;
		}

		$multipliers = [9, 1, 4, 8, 3, 10, 2, 5, 7, 6];
		$total = 0;
		for ($i = 0; $i < 10; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		// antalaron/vat-number-validator had a typo here ("total - 45", an undefined constant) which throws
		// an Error on PHP 8. Fixed according to the original braemoor algorithm.
		if ($total % 11 === 4 && $number[0] === '9') {
			$total -= 45;
		}

		$remainder = $total % 11;
		if ($remainder === 4) {
			$total = 4 - $remainder;
		}
		elseif ($remainder > 4) {
			$total = 14 - $remainder;
		}
		else {
			$total = 3 - $remainder;
		}

		return (int) $number[10] === $total;
	}

	private function checkMT(string $number): bool
	{
		$multipliers = [3, 4, 6, 7, 8, 9];
		$total = 0;
		for ($i = 0; $i < 6; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		return (int) substr($number, 6, 2) === 37 - $total % 37;
	}

	/**
	 * @param string $vat whole normalized VAT number including the NL prefix and the B suffix
	 */
	private function checkNL(string $number, string $vat): bool
	{
		$multipliers = [9, 8, 7, 6, 5, 4, 3, 2];
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = $total % 11;
		if ($total > 9) {
			$total = 0;
		}

		if ((int) $number[8] === $total) {
			return TRUE;
		}

		// Sole proprietor VAT number (since 2020): ISO 7064 MOD 97-10 over the whole VAT number
		// "NL" + 9 digits + "B" + 2 digits, letters converted to 10-35. Needs the whole number, which is why
		// antalaron/vat-number-validator read the original (not normalized) value; the library always passed
		// it a normalized value.
		$numeric = '';
		$length = strlen($vat);
		for ($i = 0; $i < $length; $i++) {
			$ord = ord($vat[$i]);
			if ($ord > 41 && $ord < 44) {
				// '*' and '+' to 36 and 37
				$numeric .= $ord - 6;
			}
			elseif ($ord > 47 && $ord < 58) {
				$numeric .= $ord - 48;
			}
			elseif ($ord > 64 && $ord < 91) {
				$numeric .= $ord - 55;
			}
			else {
				$numeric .= $ord;
			}
		}

		// Modulo of a long number computed piecewise
		$partLength = 7;
		while (strlen($numeric) > $partLength) {
			$numeric = ((int) substr($numeric, 0, $partLength) % 97) . substr($numeric, $partLength);
		}

		return (int) $numeric % 97 === 1;
	}

	private function checkNO(string $number): bool
	{
		$multipliers = [3, 2, 7, 6, 5, 4, 3, 2];
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		// Check digits > 9 are invalid
		$total = 11 - $total % 11;
		if ($total === 11) {
			$total = 0;
		}

		return (int) $number[8] === $total;
	}

	private function checkPL(string $number): bool
	{
		$multipliers = [6, 5, 7, 2, 3, 4, 5, 6, 7];
		$total = 0;
		for ($i = 0; $i < 9; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = $total % 11;
		if ($total > 9) {
			$total = 0;
		}

		return (int) $number[9] === $total;
	}

	private function checkPT(string $number): bool
	{
		$multipliers = [9, 8, 7, 6, 5, 4, 3, 2];
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = 11 - $total % 11;
		if ($total > 9) {
			$total = 0;
		}

		return (int) $number[8] === $total;
	}

	private function checkRO(string $number): bool
	{
		$length = strlen($number);
		// The multipliers are aligned to the right, the last digit is the check digit.
		$multipliers = array_slice([7, 5, 3, 2, 1, 7, 5, 3, 2], 1 - $length);
		$total = 0;
		for ($i = 0; $i < $length - 1; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = (10 * $total) % 11;
		if ($total === 10) {
			$total = 0;
		}

		return (int) substr($number, -1) === $total;
	}

	/**
	 * ISO 7064, MOD 11-10
	 */
	private function checkRS(string $number): bool
	{
		$product = 10;
		for ($i = 0; $i < 8; $i++) {
			$sum = ((int) $number[$i] + $product) % 10;
			if ($sum === 0) {
				$sum = 10;
			}
			$product = (2 * $sum) % 11;
		}

		return ($product + (int) $number[8]) % 10 === 1;
	}

	/**
	 * Russian INN
	 */
	private function checkRU(string $number): bool
	{
		if (strlen($number) === 10) {
			$multipliers = [2, 4, 10, 3, 5, 9, 4, 6, 8, 0];
			$total = 0;
			for ($i = 0; $i < 10; $i++) {
				$total += (int) $number[$i] * $multipliers[$i];
			}

			$total = $total % 11;
			if ($total > 9) {
				$total = $total % 10;
			}

			return (int) $number[9] === $total;
		}

		if (strlen($number) === 12) {
			$multipliers1 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8, 0];
			$multipliers2 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8, 0];
			$total1 = 0;
			$total2 = 0;
			for ($i = 0; $i < 11; $i++) {
				$total1 += (int) $number[$i] * $multipliers1[$i];
				$total2 += (int) $number[$i] * $multipliers2[$i];
			}

			$total1 = $total1 % 11;
			if ($total1 > 9) {
				$total1 = $total1 % 10;
			}

			$total2 = $total2 % 11;
			if ($total2 > 9) {
				$total2 = $total2 % 10;
			}

			return (int) $number[10] === $total1 && (int) $number[11] === $total2;
		}

		return FALSE;
	}

	private function checkSE(string $number): bool
	{
		// R = R1 + R3 + R5 + R7 + R9, where Ri = INT(Ci / 5) + (Ci * 2) modulo 10
		$r = 0;
		for ($i = 0; $i < 9; $i += 2) {
			$digit = (int) $number[$i];
			$r += intdiv($digit, 5) + (2 * $digit) % 10;
		}

		// S = C2 + C4 + C6 + C8
		$s = 0;
		for ($i = 1; $i < 9; $i += 2) {
			$s += (int) $number[$i];
		}

		return (int) $number[9] === (10 - ($r + $s) % 10) % 10;
	}

	private function checkSI(string $number): bool
	{
		$multipliers = [8, 7, 6, 5, 4, 3, 2];
		$total = 0;
		for ($i = 0; $i < 7; $i++) {
			$total += (int) $number[$i] * $multipliers[$i];
		}

		$total = 11 - $total % 11;
		if ($total === 10) {
			$total = 0;
		}

		return $total !== 11 && (int) $number[7] === $total;
	}

	private function checkSK(string $number): bool
	{
		// The whole number must be divisible by 11 (needs 64-bit integers).
		return (int) $number % 11 === 0;
	}

}
