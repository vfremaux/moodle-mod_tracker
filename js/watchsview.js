/**
 *
 */
// jshint undef:false, unused:false

function updatewatch(cmid, watchid, event, state, sesskey) {

    var url = M.cfg.wwwroot + '/mod/tracker/ajax/service.php';
    url += '?id=' + cmid + '&what=updatewatch&ccid=' + watchid + '&event=' + event + '&state=' + state + '&sesskey=' + sesskey;

    $.get(url, function(data, status) {
        var elmid = '#watch-' + event + '-' + watchid + '-img';
        if (!state) {
            $(elmid).addClass('tracker-shadow');
        } else {
            $(elmid).removeClass('tracker-shadow');
        }
    }, 'html');
}
