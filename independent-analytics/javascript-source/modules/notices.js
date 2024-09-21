const $ = jQuery;

const Notices = {
    setup() {
        $('#dismiss-notice').on('click', function() {
            const data = {
                ...iawpActions.confirm_cache_cleared
            };

            $('.iawp-notice.iawp-warning').hide();
    
            jQuery.post(ajaxurl, data, (response) => {

            }).fail(() => {

            });
        });
    }
}

export { Notices };