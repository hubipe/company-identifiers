<?php

declare(strict_types=1);

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Hubipe\CompanyIdentifiers\Vat\VatNumberChecker;

class EuropeanVatValidator
{

	private static ?self $instance = NULL;

	private VatNumberChecker $checker;

	private function __construct()
	{
		$this->checker = new VatNumberChecker();
	}

	public static function getInstance(): self
	{
		if (self::$instance === NULL) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function isVatValid(string $vat): bool
	{
		return $this->checker->isValid($this->formatVat($vat));
	}


	public function formatVat(string $vat): string
	{
		return VatNumberChecker::normalize($vat);
	}

}
