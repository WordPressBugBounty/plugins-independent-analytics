import { Notices } from './modules/notices';
import { StickySidebar } from './modules/sticky-sidebar';
import { Support } from './modules/support';
import { DatePicker } from './modules/date-picker';
import { Overview } from './modules/overview';

jQuery(function($) {
    StickySidebar.setup(); 
    Notices.setup();
    Support.setup();
    DatePicker.setup();
    Overview.setup();
});