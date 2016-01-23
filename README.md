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

The `insert`s happens per row and in _column_ mode all currency prices are done as one `insert`

## Disclaimer

I'm not a part of InnoExt
