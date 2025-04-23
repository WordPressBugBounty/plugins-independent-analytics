<?php

namespace IAWP;

use IAWP\Models\Geo;
/** @internal */
class Map
{
    private $country_data;
    private $title;
    private $is_showing_skeleton_ui;
    /**
     * @param Geo[] $geos
     * @param $title
     */
    public function __construct(array $country_data, $title = null, bool $is_showing_skeleton_ui = \false)
    {
        $this->country_data = $country_data;
        $this->title = $title;
        $this->is_showing_skeleton_ui = $is_showing_skeleton_ui;
    }
    public function get_html()
    {
        if ($this->is_showing_skeleton_ui) {
            $country_data = [];
        } else {
            $country_data = $this->country_data;
        }
        $dark_mode = \IAWPSCOPED\iawp()->get_option('iawp_dark_mode', '0');
        \ob_start();
        ?>
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <div class="chart-container">
            <div class="chart-inner">
                <div class="legend-container">
                    <h2 class="legend-title"><?php 
        echo $this->title;
        ?></h2>
                </div>
                <div id="independent-analytics-chart"
                     data-controller="map"
                     data-map-data-value="<?php 
        echo \esc_attr(\json_encode($country_data));
        ?>"
                     data-map-dark-mode-value="<?php 
        echo \esc_attr($dark_mode);
        ?>"
                     data-map-flags-url-value="<?php 
        echo \IAWPSCOPED\iawp_url_to('/img/flags');
        ?>"
                     data-map-locale-value="<?php 
        echo \get_bloginfo('language');
        ?>"
                >
                    <div data-map-target="chart"></div>
                </div>
            </div>
        </div><?php 
        $html = \ob_get_contents();
        \ob_end_clean();
        return $html;
    }
}
