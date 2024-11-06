<?php

namespace IAWP\Ecommerce;

use IAWP\Illuminate_Builder;
use IAWP\Models\Visitor;
use IAWP\Query;
/** @internal */
class WooCommerce_Order
{
    private $order_id;
    private $status;
    private $total;
    private $total_refunded;
    private $total_refunds;
    private $is_discounted;
    /**
     * @param int $order_id WooCommerce order ID
     */
    public function __construct(int $order_id)
    {
        $order = wc_get_order($order_id);
        $total = \intval(\round($order->get_total() * 100));
        // Total based on order currency, not shop currency
        $total_refunded = \intval(\round(\floatval($order->get_total_refunded()) * 100));
        // Refund amount based on order currency, not shop currency
        // Aelia Currency Switcher
        $aelia_exchange_rate = $order->get_meta('_base_currency_exchange_rate');
        if (\is_numeric($aelia_exchange_rate)) {
            // Exchange rate is from order currency to shop currency (multiply)
            $total = \intval(\round($total * \floatval($aelia_exchange_rate)));
            $total_refunded = \intval(\round($total_refunded * \floatval($aelia_exchange_rate)));
        }
        // WPML
        $wpml_exchange_rate = $this->wpml_exchange_rate($order->get_currency());
        if (\is_float($wpml_exchange_rate)) {
            // Exchange rate is from shop currency to order currency (divide)
            $total = \intval(\round($total / $wpml_exchange_rate));
            $total_refunded = \intval(\round($total_refunded / $wpml_exchange_rate));
        }
        // Price Based on Country for WooCommerce
        $wcpbc_exchange_rate = $order->get_meta('_wcpbc_base_exchange_rate');
        if (\is_numeric($wcpbc_exchange_rate)) {
            // Exchange rate is from order currency to shop currency (multiply)
            $total = \intval(\round($total * \floatval($wcpbc_exchange_rate)));
            $total_refunded = \intval(\round($total_refunded * \floatval($wcpbc_exchange_rate)));
        }
        $this->order_id = $order_id;
        $this->status = $order->get_status();
        $this->total = $total;
        $this->total_refunded = $total_refunded;
        $this->total_refunds = \count($order->get_refunds());
        $this->is_discounted = $this->is_discounted_order($order);
    }
    public function insert() : void
    {
        $visitor = Visitor::fetch_current_visitor();
        if (!$visitor->has_recorded_session()) {
            return;
        }
        $orders_table = Query::get_table_name(Query::ORDERS);
        Illuminate_Builder::new()->from($orders_table)->insertOrIgnore(['is_included_in_analytics' => (new \IAWP\Ecommerce\WooCommerce_Status_Manager())->is_tracked_status($this->status), 'woocommerce_order_id' => $this->order_id, 'woocommerce_order_status' => $this->status, 'view_id' => $visitor->most_recent_view_id(), 'initial_view_id' => $visitor->most_recent_initial_view_id(), 'total' => $this->total, 'total_refunded' => $this->total_refunded, 'total_refunds' => $this->total_refunds, 'is_discounted' => $this->is_discounted, 'created_at' => (new \DateTime())->format('Y-m-d H:i:s')]);
    }
    public function update() : void
    {
        $orders_table = Query::get_table_name(Query::ORDERS);
        Illuminate_Builder::new()->from($orders_table)->where('woocommerce_order_id', '=', $this->order_id)->update(['is_included_in_analytics' => (new \IAWP\Ecommerce\WooCommerce_Status_Manager())->is_tracked_status($this->status), 'woocommerce_order_status' => $this->status, 'total' => $this->total, 'total_refunded' => $this->total_refunded, 'total_refunds' => $this->total_refunds, 'is_discounted' => $this->is_discounted]);
    }
    private function is_discounted_order($order) : bool
    {
        if ($order->get_total_discount() > 0) {
            return \true;
        }
        foreach ($order->get_items() as $item) {
            if ($item->get_product()->is_on_sale()) {
                return \true;
            }
        }
        return \false;
    }
    private function wpml_exchange_rate(string $currency_code) : ?float
    {
        if (!\is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php')) {
            return null;
        }
        $wcml_options = \get_option('_wcml_settings');
        if (!\is_array($wcml_options)) {
            return null;
        }
        if (!\array_key_exists('currency_options', $wcml_options)) {
            return null;
        }
        if (!\is_array($wcml_options['currency_options']) || !\array_key_exists($currency_code, $wcml_options['currency_options'])) {
            return null;
        }
        if (!\is_array($wcml_options['currency_options'][$currency_code]) || !\array_key_exists('rate', $wcml_options['currency_options'][$currency_code])) {
            return null;
        }
        $exchange_rate = \floatval($wcml_options['currency_options'][$currency_code]['rate']);
        // Was there an error parsing value as float?
        if ($exchange_rate === 0.0) {
            return null;
        }
        return $exchange_rate;
    }
    public static function register_hooks()
    {
        // Required for block checkout
        \add_action('woocommerce_store_api_checkout_order_processed', function ($order) {
            try {
                $woocommerce_order = new self($order->get_id());
                $woocommerce_order->insert();
            } catch (\Throwable $e) {
                \error_log('Independent Analytics was unable to track the analytics for a WooCommerce order. Please report this error to Independent Analytics. The error message is below.');
                \error_log($e->getMessage());
            }
        });
        // Required for shortcode checkout
        \add_action('woocommerce_checkout_order_created', function ($order) {
            try {
                $woocommerce_order = new self($order->get_id());
                $woocommerce_order->insert();
            } catch (\Throwable $e) {
                \error_log('Independent Analytics was unable to track the analytics for a WooCommerce order. Please report this error to Independent Analytics. The error message is below.');
                \error_log($e->getMessage());
            }
        });
        \add_action('woocommerce_order_status_changed', function ($order_id) {
            try {
                $woocommerce_order = new self($order_id);
                $woocommerce_order->update();
            } catch (\Throwable $e) {
                \error_log('Independent Analytics was unable to track the analytics for a WooCommerce order. Please report this error to Independent Analytics. The error message is below.');
                \error_log($e->getMessage());
            }
        });
        // Captures a partial refund, something that woocommerce_order_status_changed will not do
        \add_action('woocommerce_order_refunded', function ($order_id) {
            try {
                $woocommerce_order = new self($order_id);
                $woocommerce_order->update();
            } catch (\Throwable $e) {
                \error_log('Independent Analytics was unable to track the analytics for a WooCommerce order. Please report this error to Independent Analytics. The error message is below.');
                \error_log($e->getMessage());
            }
        });
    }
}
