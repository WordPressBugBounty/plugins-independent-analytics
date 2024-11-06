<?php

namespace IAWP\Ecommerce;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use IAWP\Illuminate_Builder;
use IAWP\Models\Campaign;
use IAWP\Models\Referrer;
use IAWP\Query;
use IAWP\Utils\Security;
use IAWPSCOPED\Illuminate\Database\Query\JoinClause;
/** @internal */
class WooCommerce_Referrer_Meta_Box
{
    private $referrer;
    private $campaign;
    public function __construct()
    {
        \add_action('add_meta_boxes', [$this, 'maybe_add_meta_box'], 10, 2);
    }
    public function maybe_add_meta_box($post_type, $post_or_order_object) : void
    {
        if (!\IAWPSCOPED\iawp()->is_woocommerce_support_enabled()) {
            return;
        }
        $order = $post_or_order_object instanceof \WP_Post ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;
        if ($order === \false) {
            return;
        }
        $woocommerce_screen = \class_exists('\\Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController') && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id('shop-order') : 'shop_order';
        $current_screen = \get_current_screen();
        if (\is_null($current_screen) || $current_screen->id !== $woocommerce_screen) {
            return;
        }
        $referrer_row = $this->get_referrer_for($order->get_id());
        $this->referrer = \is_object($referrer_row) ? new Referrer($referrer_row) : null;
        $campaign_row = $this->get_campaign_for($order->get_id());
        $this->campaign = \is_object($campaign_row) ? new Campaign($campaign_row) : null;
        if (\is_null($this->referrer) && \is_null($this->campaign)) {
            return;
        }
        \add_meta_box('iawp-wc-referrer-source', \esc_html__('Order Referrer', 'independent-analytics'), [$this, 'render_meta_box_content'], $woocommerce_screen, 'side');
    }
    public function render_meta_box_content($post_or_order_object) : void
    {
        $order = $post_or_order_object instanceof \WP_Post ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;
        if (!\is_null($this->referrer)) {
            if ($this->referrer->has_link()) {
                $content = '<a target="_blank" href="' . \esc_url($this->referrer->referrer_url()) . '">' . Security::string($this->referrer->referrer()) . '</a>';
            } else {
                $content = Security::string($this->referrer->referrer());
            }
            echo '<p><strong>' . \esc_html__('Referrer:', 'independent-analytics') . '</strong> <span data-testid="referrer">' . $content . '</span> <a class="info-link"
                href="https://independentwp.com/knowledgebase/woocommerce/order-referrers-box/"
                target="_blank"
                style="text-decoration:none;float:right;">
                    <span class="dashicons dashicons-editor-help"></span>
                </a>
        </p>';
        }
        if (!\is_null($this->campaign)) {
            if (\is_string($this->campaign->utm_source())) {
                echo '<p><strong>' . \__('Campaign Source', 'independent-analytics') . ':</strong> <span data-testid="source">' . Security::string($this->campaign->utm_source()) . '</span> </p>';
            }
            if (\is_string($this->campaign->utm_medium())) {
                echo '<p><strong>' . \__('Campaign Medium', 'independent-analytics') . ':</strong> <span data-testid="medium">' . Security::string($this->campaign->utm_medium()) . '</span> </p>';
            }
            if (\is_string($this->campaign->utm_campaign())) {
                echo '<p><strong>' . \__('Campaign Name', 'independent-analytics') . ':</strong> <span data-testid="campaign">' . Security::string($this->campaign->utm_campaign()) . '</span> </p>';
            }
            if (\is_string($this->campaign->utm_term())) {
                echo '<p><strong>' . \__('Campaign Term', 'independent-analytics') . ':</strong> <span data-testid="term">' . Security::string($this->campaign->utm_term()) . '</span> </p>';
            }
            if (\is_string($this->campaign->utm_content())) {
                echo '<p><strong>' . \__('Campaign Content', 'independent-analytics') . ':</strong> <span data-testid="content">' . Security::string($this->campaign->utm_content()) . '</span> </p>';
            }
        }
    }
    private function get_campaign_for($order_id) : ?object
    {
        $orders_table = Query::get_table_name(Query::ORDERS);
        $campaigns_table = Query::get_table_name(Query::CAMPAIGNS);
        $views_table = Query::get_table_name(Query::VIEWS);
        $sessions_table = Query::get_table_name(Query::SESSIONS);
        $campaign_query = Illuminate_Builder::new()->select("landing_page_title AS title", "utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content")->from($orders_table, 'orders')->join("{$views_table} AS views", function (JoinClause $join) {
            $join->on('views.id', '=', 'orders.view_id');
        })->join("{$sessions_table} AS sessions", function (JoinClause $join) {
            $join->on('sessions.session_id', '=', 'views.session_id');
        })->join("{$campaigns_table} AS campaigns", function (JoinClause $join) {
            $join->on('sessions.campaign_id', '=', 'campaigns.campaign_id');
        })->where('orders.woocommerce_order_id', '=', $order_id);
        $record = $campaign_query->get()->first();
        return $record;
    }
    private function get_referrer_for($order_id) : ?object
    {
        $orders_table = Query::get_table_name(Query::ORDERS);
        $views_table = Query::get_table_name(Query::VIEWS);
        $sessions_table = Query::get_table_name(Query::SESSIONS);
        $referrer_table = Query::get_table_name(Query::REFERRERS);
        $referrer_query = Illuminate_Builder::new()->select('sessions.referrer_id', 'referrer', 'type AS referrer_type', 'domain')->from($orders_table, 'orders')->join("{$views_table} AS views", function (JoinClause $join) {
            $join->on('views.id', '=', 'orders.view_id');
        })->join("{$sessions_table} AS sessions", function (JoinClause $join) {
            $join->on('sessions.session_id', '=', 'views.session_id');
        })->join("{$referrer_table} AS referrers", function (JoinClause $join) {
            $join->on('referrers.id', '=', 'sessions.referrer_id');
        })->where('orders.woocommerce_order_id', '=', $order_id);
        $record = $referrer_query->get()->first();
        return $record;
    }
}
