<?php

namespace IAWP;

use IAWP\Click_Tracking\Link_Rule;
use IAWP\Click_Tracking\Link_Rule_Finder;
use IAWP\Models\Visitor;
use IAWP\Utils\Device;
use IAWP\Utils\Request;
use IAWP\Utils\Salt;
use IAWP\Utils\Security;
use IAWP\Utils\URL;
/** @internal */
class REST_API
{
    public function __construct()
    {
        \add_action('wp_footer', [$this, 'echo_tracking_script']);
        \add_action('rest_api_init', [$this, 'register_rest_api']);
        // Support for PDF Viewer by Themencode (free and pro versions)
        \add_action('tnc_pvfw_viewer_head', [$this, 'echo_tracking_script']);
        \add_action('tnc_pvfw_head', [$this, 'echo_tracking_script']);
        // Support for Coming Soon and Maintenance by Colorlib
        \add_action('ccsm_header', [$this, 'echo_tracking_script']);
        // Support for CMP - Coming Soon & Maintenance
        \add_action('cmp_footer', [$this, 'echo_tracking_script']);
    }
    public function echo_tracking_script()
    {
        \IAWP\Migrations\Migrations::handle_migration_18_error();
        \IAWP\Migrations\Migrations::handle_migration_22_error();
        \IAWP\Migrations\Migrations::handle_migration_29_error();
        \IAWP\Migrations\Migrations::create_or_migrate();
        if (\IAWP\Migrations\Migrations::is_migrating()) {
            return;
        }
        if (!\get_option('iawp_track_authenticated_users') && \is_user_logged_in()) {
            return;
        }
        if ($this->block_user_role()) {
            return;
        }
        // Don't track post or page previews
        if (\is_preview()) {
            return;
        }
        $payload = [];
        $current_resource = \IAWP\Resource_Identifier::for_resource_being_viewed();
        if (\is_null($current_resource)) {
            return;
        }
        $payload['resource'] = $current_resource->type();
        if ($current_resource->has_meta()) {
            $payload[$current_resource->meta_key()] = $current_resource->meta_value();
        }
        $payload['page'] = \max(1, \get_query_var('paged'));
        $data = ['payload' => $payload];
        $data['signature'] = \md5(Salt::request_payload_salt() . \json_encode($data['payload']));
        $track_view_url = \get_rest_url(null, '/iawp/search');
        $track_click_url = \IAWPSCOPED\iawp_url_to('/iawp-click-endpoint.php');
        $link_rules = Link_Rule_Finder::link_rules()->filter(function (Link_Rule $link_rule) {
            return $link_rule->is_active();
        })->map(function (Link_Rule $link_rule) {
            return ['type' => $link_rule->type(), 'value' => $link_rule->value()];
        })->values();
        $link_rules_json = \json_encode($link_rules);
        ?>
        <script>
            (function () {
                const calculateParentDistance = (child, parent) => {
                    let count = 0;
                    let currentElement = child;

                    // Traverse up the DOM tree until we reach parent or the top of the DOM
                    while (currentElement && currentElement !== parent) {
                        currentElement = currentElement.parentNode;
                        count++;
                    }

                    // If parent was not found in the hierarchy, return -1
                    if (!currentElement) {
                        return -1; // Indicates parent is not an ancestor of element
                    }

                    return count; // Number of layers between element and parent
                }
                const isMatchingClass = (linkRule, href, classes) => {
                    return classes.includes(linkRule.value)
                }
                const isMatchingDomain = (linkRule, href, classes) => {
                    if(!URL.canParse(href)) {
                        return false
                    }

                    const url = new URL(href)

                    return linkRule.value === url.host
                }
                const isMatchingExtension = (linkRule, href, classes) => {
                    if(!URL.canParse(href)) {
                        return false
                    }

                    const url = new URL(href)

                    return url.pathname.endsWith('.' + linkRule.value)
                }
                const isMatchingSubdirectory = (linkRule, href, classes) => {
                    if(!URL.canParse(href)) {
                        return false
                    }

                    const url = new URL(href)

                    return url.pathname.startsWith('/' + linkRule.value + '/')
                }
                const isMatchingProtocol = (linkRule, href, classes) => {
                    if(!URL.canParse(href)) {
                        return false
                    }

                    const url = new URL(href)

                    return url.protocol === linkRule.value + ':'
                }
                const isMatchingExternal = (linkRule, href, classes) => {
                    if(!URL.canParse(href) || !URL.canParse(document.location.href)) {
                        return false
                    }

                    const matchingProtocols = ['http:', 'https:']
                    const siteUrl = new URL(document.location.href)
                    const linkUrl = new URL(href)

                    // Links to subdomains will appear to be external matches according to JavaScript,
                    // but the PHP rules will filter those events out.
                    return matchingProtocols.includes(linkUrl.protocol) && siteUrl.host !== linkUrl.host
                }
                const isMatch = (linkRule, href, classes) => {
                    switch (linkRule.type) {
                        case 'class':
                            return isMatchingClass(linkRule, href, classes)
                        case 'domain':
                            return isMatchingDomain(linkRule, href, classes)
                        case 'extension':
                            return isMatchingExtension(linkRule, href, classes)
                        case 'subdirectory':
                            return isMatchingSubdirectory(linkRule, href, classes)
                        case 'protocol':
                            return isMatchingProtocol(linkRule, href, classes)
                        case 'external':
                            return isMatchingExternal(linkRule, href, classes)
                        default:
                            return false;
                    }
                }
                const track = (element) => {
                    const href = element.href ?? null
                    const classes = Array.from(element.classList)
                    const linkRules = <?php 
        echo $link_rules_json;
        ?>

                    if(linkRules.length === 0) {
                        return
                    }

                    // For link rules that target a class, we need to allow that class to appear
                    // in any ancestor up to the 7th ancestor. This loop looks for those matches
                    // and counts them.
                    linkRules.forEach((linkRule) => {
                        if(linkRule.type !== 'class') {
                            return;
                        }

                        const matchingAncestor = element.closest('.' + linkRule.value)

                        if(!matchingAncestor || matchingAncestor.matches('html, body')) {
                            return;
                        }

                        const depth = calculateParentDistance(element, matchingAncestor)

                        if(depth < 7) {
                            classes.push(linkRule.value)
                        }
                    });

                    const hasMatch = linkRules.some((linkRule) => {
                        return isMatch(linkRule, href, classes)
                    })

                    if(!hasMatch) {
                        return
                    }

                    const url = "<?php 
        echo $track_click_url;
        ?>";
                    const body = {
                        href: href,
                        classes: classes.join(' '),
                        ...<?php 
        echo \json_encode($data);
        ?>
                    };

                    if (navigator.sendBeacon) {
                        let blob = new Blob([JSON.stringify(body)], {
                            type: "application/json"
                        });
                        navigator.sendBeacon(url, blob);
                    } else {
                        const xhr = new XMLHttpRequest();
                        xhr.open("POST", url, true);
                        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
                        xhr.send(JSON.stringify(body))
                    }
                }
                document.addEventListener('mousedown', function (event) {
                    <?php 
        if (!\defined('IAWP_TESTING')) {
            ?>
                    if (navigator.webdriver || /bot|crawler|spider|crawling|semrushbot|chrome-lighthouse/i.test(navigator.userAgent)) {
                        return;
                    }
                    <?php 
        }
        ?>

                    const element = event.target.closest('a')

                    if(!element) {
                        return
                    }

                    const isPro = <?php 
        echo \IAWPSCOPED\iawp_is_pro() ? 'true' : 'false';
        ?>

                    if(!isPro) {
                        return
                    }

                    // Don't track left clicks with this event. The click event is used for that.
                    if(event.button === 0) {
                        return
                    }

                    track(element)
                })
                document.addEventListener('click', function (event) {
                    <?php 
        if (!\defined('IAWP_TESTING')) {
            ?>
                    if (navigator.webdriver || /bot|crawler|spider|crawling|semrushbot|chrome-lighthouse/i.test(navigator.userAgent)) {
                        return;
                    }
                    <?php 
        }
        ?>

                    const element = event.target.closest('a, button, input[type="submit"], input[type="button"]')

                    if(!element) {
                        return
                    }

                    const isPro = <?php 
        echo \IAWPSCOPED\iawp_is_pro() ? 'true' : 'false';
        ?>

                    if(!isPro) {
                        return
                    }

                    track(element)
                })
                document.addEventListener('play', function (event) {
                    <?php 
        if (!\defined('IAWP_TESTING')) {
            ?>
                    if (navigator.webdriver || /bot|crawler|spider|crawling|semrushbot|chrome-lighthouse/i.test(navigator.userAgent)) {
                        return;
                    }
                    <?php 
        }
        ?>

                    const element = event.target.closest('audio, video')

                    if(!element) {
                        return
                    }

                    const isPro = <?php 
        echo \IAWPSCOPED\iawp_is_pro() ? 'true' : 'false';
        ?>

                    if(!isPro) {
                        return
                    }

                    track(element)
                }, true)
                document.addEventListener("DOMContentLoaded", function (e) {
                    if (document.hasOwnProperty("visibilityState") && document.visibilityState === "prerender") {
                        return;
                    }

                    <?php 
        if (!\defined('IAWP_TESTING')) {
            ?>
                        if (navigator.webdriver || /bot|crawler|spider|crawling|semrushbot|chrome-lighthouse/i.test(navigator.userAgent)) {
                            return;
                        }
                    <?php 
        }
        ?>

                    let referrer_url = null;

                    if (typeof document.referrer === 'string' && document.referrer.length > 0) {
                        referrer_url = document.referrer;
                    }

                    const params = location.search.slice(1).split('&').reduce((acc, s) => {
                        const [k, v] = s.split('=');
                        return Object.assign(acc, {[k]: v});
                    }, {});

                    const url = "<?php 
        echo $track_view_url;
        ?>";
                    const body = {
                        referrer_url,
                        utm_source: params.utm_source,
                        utm_medium: params.utm_medium,
                        utm_campaign: params.utm_campaign,
                        utm_term: params.utm_term,
                        utm_content: params.utm_content,
                        gclid: params.gclid,
                        ...<?php 
        echo \json_encode($data);
        ?>
                    };

                    if (navigator.sendBeacon) {
                        let blob = new Blob([JSON.stringify(body)], {
                            type: "application/json"
                        });
                        navigator.sendBeacon(url, blob);
                    } else {
                        const xhr = new XMLHttpRequest();
                        xhr.open("POST", url, true);
                        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
                        xhr.send(JSON.stringify(body))
                    }
                });
            })();
        </script>
        <?php 
    }
    public function register_rest_api()
    {
        \register_rest_route('iawp', '/search', ['methods' => 'POST', 'callback' => [$this, 'track_view'], 'permission_callback' => function () {
            return \true;
        }]);
    }
    public function track_view($request)
    {
        if (Device::getInstance()->is_bot() && !\defined('IAWP_TESTING')) {
            return;
        }
        \IAWP\Migrations\Migrations::handle_migration_18_error();
        \IAWP\Migrations\Migrations::handle_migration_22_error();
        \IAWP\Migrations\Migrations::handle_migration_29_error();
        \IAWP\Migrations\Migrations::create_or_migrate();
        if (\IAWP\Migrations\Migrations::is_migrating()) {
            return;
        }
        if (Request::is_ip_address_blocked()) {
            return;
        }
        $visitor = Visitor::fetch_current_visitor();
        $signature = \md5(Salt::request_payload_salt() . \json_encode($request['payload']));
        $campaign = [];
        if (\IAWPSCOPED\iawp_is_pro()) {
            $campaign = ['utm_source' => $this->decode_or_nullify($request['utm_source']), 'utm_medium' => $this->decode_or_nullify($request['utm_medium']), 'utm_campaign' => $this->decode_or_nullify($request['utm_campaign']), 'utm_term' => $this->decode_or_nullify($request['utm_term']), 'utm_content' => $this->decode_or_nullify($request['utm_content'])];
        }
        if ($signature == $request['signature']) {
            new \IAWP\View($request['payload'], $this->calculate_referrer_url($request), $visitor, $campaign);
            return new \WP_REST_Response(['success' => \true], 200, ['X-IAWP' => 'iawp']);
        } else {
            return new \WP_REST_Response(['success' => \false], 200, ['X-IAWP' => 'iawp']);
        }
    }
    private function calculate_referrer_url($request) : ?string
    {
        $referrer_url = $request['referrer_url'];
        $url = new URL($referrer_url ?? '');
        if (!\is_null($this->decode_or_nullify($request['gclid'])) && $url->get_domain() !== 'googleads.g.doubleclick.net') {
            $referrer_url = 'https://googleads.iawp';
        }
        if (\is_null($referrer_url)) {
            return null;
        }
        return $referrer_url;
    }
    private function decode_or_nullify($string)
    {
        if (!isset($string)) {
            return null;
        }
        $safe_string = \trim(\urldecode($string));
        $safe_string = \str_replace('+', ' ', $safe_string);
        $safe_string = Security::string($safe_string);
        if (\strlen($safe_string) === 0) {
            return null;
        }
        return $safe_string;
    }
    private function block_user_role() : bool
    {
        $blocked_roles = \IAWPSCOPED\iawp()->get_option('iawp_blocked_roles', ['administrator']);
        foreach (\wp_get_current_user()->roles as $visitor_role) {
            if (\in_array($visitor_role, $blocked_roles)) {
                return \true;
            }
        }
        return \false;
    }
}
