# Currency Pricing

Magmi itemprocessor plugin for importing currency price for the magento extension http://innoexts.com/currency-pricing/

## Usage

The plugin supports to formats: _row_ or _column_

_row_

    sku,price,currency

Include a line for each currency for each product:

    N10-XL,99.95,USD
    N10-XL,89.95,GBP

_column_

    sku,price_<CURRENCY_1>[,price_<CURRENCY_N>]

Include a price column for each currency:

    sku,price_USD,price_GBP
    N10-XL,99.95,89.95

The `insert`s happens per row and in _column_ mode all currency prices are done as one `insert`.

### Mode

The plugin determins the mode based on column names. The default mode is _row_ mode.

If one column is found matching the pattern `price_<CURRENCY>` mode is switched to _column_.

## Columns

* `store|website` (optional) - comma separated list website codes. Default: admin
* `sku` - _sku_ of product to update

For _row_ mode, these are required:

* `price` - product price
* `currency` - currency of product price

For _column_ mode any number of columns with this syntax:

* `price_<CURRENCY>` - the product price in the currency specified by column name `<CURRENCY>`

Note: _row_ mode columns will be ignored when in _column_ mode

## Change log

### 0.3.0

* Support for `store` or `website` column (synonyms).
* Renamed class name using upper CamelCase, so you need to update your plugins config file in magmi.

### 0.2.0

* Support for `price_<CURRENCY>` column syntax.

## TODO

* Make it work for _special price_

## Disclaimer

I'm not a part of InnoExt
