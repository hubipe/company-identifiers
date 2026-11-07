<?php

declare(strict_types=1);

namespace Hubipe\CompanyIdentifiers\CountryValidators;

interface CountryValidator
{

	public function isCompanyIdValid(string $id): bool;

	public function formatCompanyId(string $id): string;

	public function getCompanyIdentifier(string $id): string;

	public function isCompanyVatIdValid(string $vatId): bool;

	public function formatCompanyVatId(string $vatId): string;

}
