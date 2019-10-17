


define(['jquery', 'core/config', 'core/str', 'core/log'], function($, cfg, str, log) {

    var moodletracker = {

        strs: [],

        init: function() {

            var stringdefs = [
                {key: 'showccs', component: 'tracker'}, // 0
                {key: 'hideccs', component: 'tracker'}, // 1
                {key: 'showcomments', component: 'tracker'}, // 2
                {key: 'hidecomments', component: 'tracker'}, // 3
                {key: 'showdependancies', component: 'tracker'}, // 4
                {key: 'hidedependancies', component: 'tracker'}, // 5
                {key: 'showhistory', component: 'tracker'}, // 6
                {key: 'hidehistory', component: 'tracker'}, // 7
                {key: 'enlarge', component: 'tracker'}, // 8
                {key: 'shrink', component: 'tracker'} // 9
            ];

            str.get_strings(stringdefs).done(function(s) {
                moodletracker.strs = s;
            });

            $('.watch-handle').off('click');
            $('.watch-handle').bind('click', this.togglewatch);
            $('#tracker-issuehistory-handle').off('click');
            $('#tracker-issuehistory-handle').bind('click', this.togglehistory);
            $('#tracker-issueccs-handle').off('click');
            $('#tracker-issueccs-handle').bind('click', this.toggleccs);
            $('#tracker-issuedependancies-handle').off('click');
            $('#tracker-issuedependancies-handle').bind('click', this.toggledependancies);
            $('#tracker-issuecomments-handle').off('click');
            $('#tracker-issuecomments-handle').bind('click', this.togglecomments);
            $('.image-enlarge-handle').bind('click', this.enlargeswitch);

            log.debug('AMD Tracker initialized');
        },

        togglehistory: function (e) {
            var historydiv = $('#tracker-issuehistory');
            var historylink = $('#tracker-issuehistory-handle');
            if (historydiv.css('display') === 'block') {
                historydiv.css('display', 'none');
                historylink.html(moodletracker.strs[6]);
            } else {
                historydiv.css('display', 'block');
                historylink.html(moodletracker.strs[7]);
            }
            e.stopPropagation();
            e.preventDefault();
        },

        toggleccs: function (e) {
            var ccsdiv = $('#tracker-issueccs');
            var ccslink = $('#tracker-issueccs-handle');
            if (ccsdiv.css('display') === 'block') {
                ccsdiv.css('display', 'none');
                ccslink.html(moodletracker.strs[0]);
            } else {
                ccsdiv.css('display', 'block');
                ccslink.html(moodletracker.strs[1]);
            }
            e.stopPropagation();
            e.preventDefault();
        },

        toggledependancies: function (e) {
            var dependanciesdiv = $("#tracker-issuedependancies");
            var dependancieslink = $("#tracker-issuedependancies-handle");
            if (dependanciesdiv.css('display') === 'block') {
                dependanciesdiv.css('display', 'none');
                dependancieslink.html(moodletracker.strs[4]);
            } else {
                dependanciesdiv.css('display', 'block');
                dependancieslink.html(moodletracker.strs[5]);
            }
            e.stopPropagation();
            e.preventDefault();
        },

        togglecomments: function (e) {
            var commentdiv = $("#tracker-issuecomments");
            var commentlink = $("#tracker-issuecomments-handle");
            if (commentdiv.css('display') === 'block') {
                commentdiv.css('display', 'none');
                commentlink.html(moodletracker.strs[2]);
            } else {
                commentdiv.css('display', 'block');
                commentlink.html(moodletracker.strs[3]);
            }
            e.stopPropagation();
            e.preventDefault();
        },

        enlargeswitch : function() {
            var that = $(this);

            var imageid = that.attr('id').replace('image-enlarge-', 'issue-image-');

            var currentwidth = $('#' + imageid).css('max-width');
            if (currentwidth == '600px') {
                $('#' + imageid).css('max-width', null);
                this.html(moodletracker.strs[9]);
            } else {
                $('#' + imageid).css('max-width', '600px');
                this.html(moodletracker.strs[8]);
            }
        }
    };

    return moodletracker;
});