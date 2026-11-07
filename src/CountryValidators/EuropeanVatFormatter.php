<?php

declare(strict_types=1);

namespace Hubipe\CompanyIdentifiers\CountryValidators;

trait EuropeanVatFormatter
{

	public function formatCompanyVatId(string $vatId): string
	{
		return EuropeanVatValidator::getInstance()->formatVat($vatId);
	}


}
