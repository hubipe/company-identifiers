<?php

declare(strict_types=1);

namespace Hubipe\CompanyIdentifiers\Tests\Vat;

use Hubipe\CompanyIdentifiers\Vat\VatNumberChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VatNumberCheckerTest extends TestCase
{

	/**
	 * @return iterable<string, array{string, bool}>
	 */
	public static function antalaronVatNumberProvider(): iterable
	{
		/** @var list<array{string, bool}> $vectors */
		$vectors = require __DIR__ . '/antalaron-vat-numbers.php';
		foreach ($vectors as $index => [$vat, $valid]) {
			// the vectors contain duplicates
			yield sprintf('#%d %s', $index, $vat) => [$vat, $valid];
		}
	}

	#[DataProvider('antalaronVatNumberProvider')]
	public function testAntalaronVatNumbers(string $vat, bool $expected): void
	{
		self::assertSame($expected, (new VatNumberChecker())->isValid($vat));
	}

	/**
	 * @return iterable<string, array{string, bool}>
	 */
	public static function edgeCaseProvider(): iterable
	{
		// empty number is valid (antalaron/vat-number-validator behaviour)
		yield 'empty' => ['', TRUE];
		yield 'whitespace only' => [" \t\n", TRUE];
		yield 'separators only' => ['- . -', TRUE];

		yield 'lowercase' => ['cz25596641', TRUE];
		yield 'spaces' => ['CZ 255 966 41', TRUE];
		yield 'dashes' => ['CZ-25596641', TRUE];
		yield 'dots' => ['CZ.255.966.41', TRUE];
		yield 'invalid check digit' => ['CZ25596642', FALSE];

		yield 'unknown prefix' => ['XX123456789', FALSE];
		yield 'no prefix' => ['11316385-2-18', FALSE];
		yield 'prefix only' => ['CZ', FALSE];
		yield 'GR instead of EL' => ['GR094259216', FALSE];

		yield 'CZ 13 digits, last three ignored' => ['CZ25596641123', TRUE];
		yield 'CZ 11 digits' => ['CZ255966411', FALSE];

		yield 'CHE with separators and suffix' => ['CHE-123.456.788 MWST', TRUE];
		yield 'CHE lowercase' => ['che123456788tva', TRUE];

		// sole proprietor number (MOD 97-10 over the whole number)
		yield 'NL sole proprietor' => ['NL000099998B57', TRUE];
		yield 'NL sole proprietor with spaces, lowercase' => ['nl 0000 99998 b 57', TRUE];
		yield 'NL sole proprietor invalid' => ['NL000099998B58', FALSE];

		// antalaron/vat-number-validator never accepted these (undefined variable in its check)
		yield 'ES personal entity starting with X' => ['ESX1234567L', FALSE];
		yield 'ES personal entity starting with K' => ['ESK1234567L', FALSE];

		// antalaron/vat-number-validator threw an Error for these (typo in its check)
		yield 'LV legal entity, special case, valid' => ['LV91658602190', TRUE];
		yield 'LV legal entity, special case, invalid' => ['LV98742412402', FALSE];
	}

	#[DataProvider('edgeCaseProvider')]
	public function testEdgeCases(string $vat, bool $expected): void
	{
		self::assertSame($expected, (new VatNumberChecker())->isValid($vat));
	}

	public function testNormalize(): void
	{
		self::assertSame('NL000099998B57', VatNumberChecker::normalize(" nl-0000.99998 \tb 57\n"));
		self::assertSame('', VatNumberChecker::normalize(' - . '));
	}

}
