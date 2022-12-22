<?php

namespace Hubipe\CompanyIdentifiers\CountryValidators;

use Antalaron\Component\VatNumberValidator\VatNumber;
use Consistence\ObjectPrototype;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EuropeanVatValidator extends ObjectPrototype
{

	/** @var static|null */
	private static $instance = NULL;

	/** @var ValidatorInterface */
	private $validator;

	private function __construct()
	{
		$this->validator = Validation::createValidator();
	}

	public static function getInstance(): self
	{
		if (self::$instance === NULL) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function isVatValid(string $vat): bool
	{
		$formattedVat = $this->formatVat($vat);

		$violations = $this->validator->validate($formattedVat, new VatNumber());
		return count($violations) === 0;
	}


	public function formatVat(string $vat): string
	{
		return preg_replace('/(\s|-|\.)+/', '', strtoupper($vat));
	}

}
