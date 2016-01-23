<?php

/**
 * Currency Price item processor for InnoExt Currency Pricing Magento extension
 *
 * Needs this extension to work: http://innoexts.com/currency-pricing/
 *
 * @author mblarsen
 *
 */
class currencypriceprocessor extends Magmi_ItemProcessor
{
    const MODE_ROWS    = 1; // currency, price
    const MODE_COLUMNS = 2; // price_USD, price_DKK
    
    protected $_mode               = 0;
    protected $_col_currencies     = [];
    protected $_allowed_currencies = [];
    protected $_early_fail         = false;
    protected $_singlestore        = 0;
    // catalog/price/scope 0       = global, 1=website
    protected $__pricescope        = 2;

    public function getPluginInfo()
    {
        return [
            "name"    => "Currency Price importer",
            "author"  => "mblarsen",
            "version" => "0.2.0"
        ];
    }

    public function preprocessItemAfterId(&$item, $fullmeta = null)
    {
        if ($this->_early_fail) {
            return false;
        }
    }

    /**
     * you can add/remove columns for the item passed since it is passed by reference
     *
     * @param unknown_type $item
     *            : modifiable reference to item before import
     *            the $item is a key/value array with column names as keys and values as read from csv file.
     * @return bool :
     *         true if you want the item to be imported after your custom processing
     *         false if you want to skip item import after your processing
     */
    public function processItemAfterId(&$item, $params = null)
    {
        // catacatalog_product_compound_price_product_compound_price
        // catalog_product_compound_special_price
        $sku     = $params["product_id"];
        $table   = $this->tablename("catalog_product_compound_price");
        $website = isset($item["website"]) && !empty($item["website"]) ? $item["website"] : 0;
        $prices  = $this->getPrices($item);

        if (count($prices)) {
            $sql = "INSERT INTO $table (product_id, currency, website_id, price) VALUES " .
                join(",\n", array_fill(0, count($prices), "(?, ?, ?, ?)")) . "\n" .
                "ON DUPLICATE KEY UPDATE `price`= VALUES(`price`)";

            $bind = [];
            foreach ($prices as $price_currency_set) {
                list($price, $currency) = $price_currency_set;
                $bind[] = $sku;
                $bind[] = $currency;
                $bind[] = $website;
                $bind[] = $price;
            }

            $this->insert($sql, $bind);
        }

        return true;
    }
    
    private function getPrices($item)
    {
        if (self::MODE_ROWS === $this->_mode) {
            return [ [ (float) $item[$price], strtoupper($item["currency"]) ] ];
        }

        // else if (self::MODE_COLUMNS === $this->_mode)
        $prices = [];
        foreach ($this->_col_currencies as $col => $currency) {
            $prices[] = [ (float) $item[$col], strtoupper($currency) ];
        }

        return $prices;
    }

    public function processColumnList(&$cols, $params = null)
    {
        $this->_mode = self::MODE_COLUMNS;
        if (in_array("currency", $cols)) {
            $this->_mode = self::MODE_ROWS;
        }
        
        $this->log("Setting currency mode: " . ($this->_mode === self::MODE_COLUMNS ? "columns" : "rows"), "info");

        if (self::MODE_COLUMNS === $this->_mode) {
            foreach ($cols as $col) {
                if (preg_match("~price_[A-Za-z]{3}~", $col)) {
                    $this->_col_currencies[$col] = substr($col, -3);
                }
            }
            $this->log("Identified currencies: " . join(", ", array_values($this->_col_currencies)), "info");
        }

        $non_configured_currencies = array_diff(array_values($this->_col_currencies), $this->_allowed_currencies);

        if (count($non_configured_currencies)) {
            $this->_early_fail = true;
            $this->log("Non configured currencies: " . join(", ", $non_configured_currencies), "error");
            return false;
        }

        return true;
    }

    /**
     * Lookup stores and price settings
     */
    public function initialize($params)
    {
        // Check if extension is installed
        $sql = "SHOW TABLES LIKE 'catalog_product_compound_price'";
        $required_tables_count = count($this->selectAll($sql));
        if ($required_tables_count === 0) {
            $this->_early_fail = true;
            $this->log("Currency price tables are missing. It seems that the extension has not been installed", "error");
            return false;
        }
        
        // Get currencies
        $sql = "SELECT value FROM " . $this->tablename("core_config_data") . " WHERE path = ?";
        $this->_allowed_currencies = explode(",", $this->selectOne($sql, array("currency/options/allow"), "value"));

        // Store settings
        $sql = "SELECT COUNT(store_id) as cnt FROM " . $this->tablename("core_store") . " WHERE store_id != 0";
        $store_count = $this->selectOne($sql, array(), "cnt");
        if ($store_count == 1) {
            $this->_singlestore = 1;
        }
        
        // Price scope
        $sql = "SELECT value FROM " . $this->tablename("core_config_data") . " WHERE path = ?";
        $this->_pricescope = intval($this->selectOne($sql, array("catalog/price/scope"), "value"));
    }
}
