# Company identifiers library
Library aims to validate national identifiers formats and VAT numbers formats of companies and business individuals.
The library does not validate actual existence of company with given ID nor the VAT registration in the registers.
It purely validates the formats.

The library contains methods to validate format of these business identifiers plus for all the countries also the VAT number format:

| country         | business identifier name | business identifier format   | check number       |
| ----------------| -------------------------|------------------------------|--------------------|
| Austria         | Firmenbuchnummer         | FN 123456a                   | :heavy_check_mark: |
| Bulgaria        | ?                        | 123456789                    | :heavy_check_mark: |
| Croatia         | ?                        | 12345678901                  | :x:                |
| Czech Republic  | IČO                      | 12345678 or 7501011234       | :heavy_check_mark: |
| Germany         | Handelsregisternummer    | HRA/HRB/GNR/PR/VR 123456A    | :x:                |
| Hungary         | Adoszám                  | 12345678-1-11                | :heavy_check_mark: |
| Poland          | REGON                    | 123456789 or 12345678901234  | :heavy_check_mark: |
| Slovakia        | IČO                      | 12345678                     | :heavy_check_mark: |
| Slovenia        | Matična številka         | 1234567 or 1234567000        | :warning:          |

* :heavy_check_mark: check number implemented
* :x: the identifier does not contain the check number
* :warning: identifier contain check number, the algorithm is not known by library
* :question: identifier check number presence is unknown

The check number is not available in all formats or it has not been implemented yet. If you know the algorithm for the check number calculation
and the library does not contain the check validation, feel free to create issue or PR.

Also if you know any other country or company ID/business individuals identifier formats/check digits algorithms, create an issue with link to
relevant source or create a pull request.

## Installation

```
composer require hubipe/company-identifiers
```
