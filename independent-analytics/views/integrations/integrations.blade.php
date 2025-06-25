@php /** @var \IAWP\Integrations\Integrations $integrations */ @endphp

<div class="integrations-menu">
    <div class="integrations-menu-inner">
        <h1>Integrations</h1>
        <div class="integration-category">
            <h2>eCommerce Plugin Integrations</h2>
            @if(iawp_is_pro())
                @if($integrations->is_using_ecommerce_plugin())
                    <p class="iawp-category-description">Your site is actively tracking all sales made with <strong>{{ esc_html($integrations->active_ecommerce_plugin()->name()) }}</strong> thanks to the Independent Analytics Pro plugin! <a target="_blank" href="https://independentwp.com/knowledgebase/woocommerce/woocommerce-integration/">Watch video tutorial <span class="dashicons dashicons-external"></span></a></p>
                @else
                    <p class="iawp-category-description">Automatically track orders, conversion rates, total sales, refunds, and more in the Pages, Referrers, Geographic, 
                        Devices, and Campaigns reports. <a target="_blank" href="https://independentwp.com/knowledgebase/woocommerce/woocommerce-integration/">Watch video tutorial <span class="dashicons dashicons-external"></span></a></p>
                @endif
            @else
                @if($integrations->is_using_ecommerce_plugin())
                    <p class="iawp-category-description"><strong>You're missing out on data!</strong> You could be tracking <strong>{{ esc_html($integrations->active_ecommerce_plugin()->name()) }}</strong> orders, conversion rates, total sales, refunds, and more in the Pages, Referrers, Geographic, 
                        Devices, and Campaigns reports. <a target="_blank" href="https://independentwp.com/features/woocommerce-analytics/?utm_source=User+Dashboard&utm_medium=WP+Admin&utm_campaign=Integrations+Menu">Learn more <span class="dashicons dashicons-external"></span></a></p>
                @else
                    <p class="iawp-category-description">Automatically track orders, conversion rates, total sales, refunds, and more in the Pages, Referrers, Geographic, 
                        Devices, and Campaigns reports using Independent Analytics Pro. <a target="_blank" href="https://independentwp.com/features/woocommerce-analytics/?utm_source=User+Dashboard&utm_medium=WP+Admin&utm_campaign=Integrations+Menu">Learn more <span class="dashicons dashicons-external"></span></a></p>
                @endif
            @endif
            <div class="iawp-integration-list">
                @foreach($integrations->ecommerce_integrations() as $integration)
                    {!! $integration->html() !!}
                @endforeach
            </div>
        </div>
        <div class="integration-category">
            <h2>Form Plugin Integrations</h2>
            @if(iawp_is_pro())
                @if($integrations->is_using_form_plugin())
                    <p class="iawp-category-description">Your site is actively tracking all form submissions made with <strong>{{ esc_html($integrations->active_form_plugin()->name()) }}</strong> thanks to the Independent Analytics Pro plugin! <a target="_blank" href="https://independentwp.com/knowledgebase/form-tracking/track-form-submissions/">Watch video tutorial <span class="dashicons dashicons-external"></span></a></p>
                @else
                    <p class="iawp-category-description">Automatically track form submissions and conversion rates in the Pages, Referrers, Geographic, 
                        Devices, and Campaigns reports. <a target="_blank" href="https://independentwp.com/knowledgebase/form-tracking/track-form-submissions/">Watch video tutorial <span class="dashicons dashicons-external"></span></a></p>
                @endif
            @else
                @if($integrations->is_using_form_plugin())
                    <p class="iawp-category-description"><strong>You're missing out on data!</strong> You could be tracking <strong>{{ esc_html($integrations->active_form_plugin()->name()) }}</strong> submissions and conversion rates in the Pages, Referrers, Geographic, 
                        Devices, and Campaigns reports. <a target="_blank" href="https://independentwp.com/features/form-tracking/?utm_source=User+Dashboard&utm_medium=WP+Admin&utm_campaign=Integrations+Menu">Learn more <span class="dashicons dashicons-external"></span></a></p>
                @else
                <p class="iawp-category-description">Automatically track form submissions and conversion rates for every form in the Pages, Referrers, 
                    Geographic, Devices, and Campaigns reports using Independent Analytics Pro. <a target="_blank" href="https://independentwp.com/features/form-tracking/?utm_source=User+Dashboard&utm_medium=WP+Admin&utm_campaign=Integrations+Menu">Learn more <span class="dashicons dashicons-external"></span></a></p>
                @endif
            @endif
                
            <div class="iawp-integration-list">
                @foreach($integrations->form_integrations() as $integration)
                    {!! $integration->html() !!}
                @endforeach
            </div>
        </div>
    </div>
</div>