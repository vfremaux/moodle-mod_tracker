/**
 *
 */
// jshint undef:false, unused:false

define(['jquery', 'core/config', 'core/str', 'core/log'], function($, cfg, str, log) {

    var trackerwatches = {

        init: function() {
            $('.tracker-events-prefs').bind('click', this.updatewatch);

            log.debug('AMD Tracker watch prefs initialized');
        },

        updatewatch : function() {

            var that = $(this);
            var regex = /(\d+)-(\d+)-(\w+)-(\w+)/;
            var parts = that.attr('id').match(regex);
            var cmid = parts[1];
            var watchid = parts[2];
            var event = parts[3];
            var state = parts[4];

            var url = cfg.wwwroot + '/mod/tracker/ajax/service.php';
            url += '?id=' + cmid;
            url += '&what=updatewatch';
            url += '&ccid=' + watchid;
            url += '&event=' + event;
            url += '&state=' + state;
            url += '&sesskey=' + cfg.sesskey;

            $.get(url, function() {
                var elmid = '#watch-' + event + '-' + watchid + '-img';
                if (state != 1) {
                    $(elmid).addClass('tracker-shadow');
                } else {
                    $(elmid).removeClass('tracker-shadow');
                }
                var nextstate = (state) ? 0 : 1;
                that.attr('id', cmid + '-' + watchid + '-' + event + '-' + nextstate);
            }, 'html');
        }
    };

    return trackerwatches;
});