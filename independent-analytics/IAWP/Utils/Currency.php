<?php

namespace IAWP\Utils;

use IAWP\Ecommerce\SureCart_Store;
use IAWPSCOPED\Illuminate\Support\Str;
/** @internal */
class Currency
{
    public static function format(int $amount_in_cents, bool $round_to_whole_dollars = \true) : string
    {
        if ($round_to_whole_dollars) {
            $amount_in_cents = \round($amount_in_cents, -2);
        }
        if (\function_exists('wc_price')) {
            if ($round_to_whole_dollars) {
                $options = ['decimals' => 0];
            } else {
                $options = null;
            }
            $formatted_value = \strip_tags(\wc_price($amount_in_cents / 100, $options));
            return $formatted_value;
        }
        if (\class_exists('\\SureCart\\Support\\Currency')) {
            $currency_code = SureCart_Store::get_currency_code();
            $formatted_value = \SureCart\Support\Currency::format($amount_in_cents, $currency_code);
            return $round_to_whole_dollars ? Str::before($formatted_value, ".") : $formatted_value;
        }
        // Fallback
        return \strval(\intval($amount_in_cents / 100));
    }
}
