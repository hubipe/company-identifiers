<?php

declare(strict_types=1);

namespace Hubipe\CompanyIdentifiers\Tests\CountryValidators;

use Hubipe\CompanyIdentifiers\CountryValidators\Austria;
use Hubipe\CompanyIdentifiers\CountryValidators\Bulgaria;
use Hubipe\CompanyIdentifiers\CountryValidators\CountryValidator;
use Hubipe\CompanyIdentifiers\CountryValidators\Croatia;
use Hubipe\CompanyIdentifiers\CountryValidators\CzechRepublic;
use Hubipe\CompanyIdentifiers\CountryValidators\EuropeanVatValidator;
use Hubipe\CompanyIdentifiers\CountryValidators\Germany;
use Hubipe\CompanyIdentifiers\CountryValidators\Greece;
use Hubipe\CompanyIdentifiers\CountryValidators\Hungary;
use Hubipe\CompanyIdentifiers\CountryValidators\Poland;
use Hubipe\CompanyIdentifiers\CountryValidators\Slovakia;
use Hubipe\CompanyIdentifiers\CountryValidators\Slovenia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EuropeanVatValidatorTest extends TestCase
{

	public function testIsVatValid(): void
	{
		$validator = EuropeanVatValidator::getInstance();

		self::assertTrue($validator->isVatValid('cz 255 966 41'));
		self::assertFalse($validator->isVatValid('CZ25596642'));
		self::assertTrue($validator->isVatValid('nl 0000 99998 b 57'));
		self::assertFalse($validator->isVatValid('XX123456789'));
		// empty VAT number is valid (antalaron/vat-number-validator behaviour)
		self::assertTrue($validator->isVatValid(''));
		self::assertTrue($validator->isVatValid(' - '));
	}

	public function testFormatVat(): void
	{
		self::assertSame('CZ25596641', EuropeanVatValidator::getInstance()->formatVat(" cz-255.966 41\t"));
	}

	/**
	 * @return iterable<string, array{CountryValidator, string, bool}>
	 */
	public static function countryVatProvider(): iterable
	{
		yield 'Austria valid' => [new Austria(), 'ATU 1234 5675', TRUE];
		yield 'Austria invalid' => [new Austria(), 'ATU12345678', FALSE];
		yield 'Bulgaria valid' => [new Bulgaria(), 'BG175074752', TRUE];
		yield 'Bulgaria invalid' => [new Bulgaria(), 'BG175074753', FALSE];
		yield 'Croatia valid' => [new Croatia(), 'HR33392005961', TRUE];
		yield 'Croatia invalid' => [new Croatia(), 'HR33392005962', FALSE];
		yield 'Czech Republic valid' => [new CzechRepublic(), 'CZ25596641', TRUE];
		yield 'Czech Republic invalid' => [new CzechRepublic(), 'CZ25596642', FALSE];
		yield 'Czech Republic other prefix' => [new CzechRepublic(), 'SK2020273893', FALSE];
		yield 'Germany valid' => [new Germany(), 'DE136695976', TRUE];
		yield 'Germany invalid' => [new Germany(), 'DE136695977', FALSE];
		yield 'Greece valid' => [new Greece(), 'EL094259216', TRUE];
		yield 'Greece invalid' => [new Greece(), 'EL094259217', FALSE];
		yield 'Hungary valid' => [new Hungary(), 'HU12783166', TRUE];
		yield 'Hungary invalid' => [new Hungary(), 'HU12783167', FALSE];
		yield 'Poland valid' => [new Poland(), 'PL5252248481', TRUE];
		yield 'Poland invalid' => [new Poland(), 'PL5252248482', FALSE];
		yield 'Slovakia valid' => [new Slovakia(), 'SK2020273893', TRUE];
		yield 'Slovakia invalid' => [new Slovakia(), 'SK2020273894', FALSE];
		yield 'Slovenia valid' => [new Slovenia(), 'SI50223054', TRUE];
		yield 'Slovenia invalid' => [new Slovenia(), 'SI50223055', FALSE];
	}

	#[DataProvider('countryVatProvider')]
	public function testCountryVat(CountryValidator $validator, string $vat, bool $expected): void
	{
		self::assertSame($expected, $validator->isCompanyVatIdValid($vat));
	}

}
