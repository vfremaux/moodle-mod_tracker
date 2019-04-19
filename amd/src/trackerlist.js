


define(['jquery', 'core/config', 'core/log'], function($, cfg, log) {

    var moodletrackerlist = {

        init: function() {

            $(".tracker-issue-edit-handle").bind('click', this.load_change_form);
            $("#modal-status-save").bind('click', this.submit_change_form);
            log.debug('AMD Tracker List initialized');
        },

        load_change_form: function() {
            var that = $(this);

            var id = that.attr('id').replace(/issue-edit-(.*?)-handle-/, '');
            var mode = that.attr('data-mode');
            var ctx = that.attr('data-context');

            var url = cfg.wwwroot + '/mod/tracker/ajax/service.php';
            url += '?what=getmodalform';
            url += '&mode=' + mode;
            url += '&id=' + id;
            url += '&ctx=' + ctx;

            $.get(url, function(data) {
                $('#issuelist-edit-inner-form').html(data);
                $("#modal-status-save").attr('data-issue', id);
                $("#modal-status-save").attr('data-purpose', mode);
                $('.selectpicker').selectpicker();
            }, 'html');
        },

        submit_change_form: function() {
            var that = $(this);

            var issueid = that.attr('data-issue');
            var purpose = that.attr('data-purpose');
            var url = cfg.wwwroot + '/mod/tracker/ajax/service.php';
            url += '?what=update' + purpose;
            url += '&id=' + issueid;
            var selectkey = '#' + purpose + '-select-' + issueid;
            url += '&' + purpose + '=' + $(selectkey).val();

            $.get(url, function(data) {
                if (data.result === 'success') {
                    if (purpose == 'status') {
                        var oldclassname = 'status-' + moodletrackerlist.get_status_code(data.oldvalue);
                        $('.issue-list-status-' + issueid).removeClass(oldclassname);
                        var newclassname = 'status-' + moodletrackerlist.get_status_code(data.newvalue);
                        $('.issue-list-status-' + issueid).addClass(newclassname);
                    }
                    $('#tracker-' + purpose + '-' + issueid).html(data.newlabel);
                    $('#issuelist-edit-form').modal('hide'); // Close the modal dialog.
                }
            }, 'json');
        },

        get_status_code: function (statusix) {
            var statuscodes = ['posted',
                'open',
                'resolving',
                'waiting',
                'resolved',
                'abandonned',
                'transfered',
                'testing',
                'validated',
                'published'
            ];

            return statuscodes[statusix];
        }
    };

    return moodletrackerlist;
});