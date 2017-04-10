/**
 *
 */
// jshint undef:false, unused:false

function updatewatchs(cmid, watchid, event, state, sesskey) {

    url = M.cfg.wwwroot + '/mod/tracker/ajax/service.php';
    url += '?id=' + cmid + '&what=updatewatch&ccid=' + watchid + '&event=' + event + '&state=' + state + '&sesskey' + sesskey;

    $.get(url, function(data, status) {
        if (!state) {
            $('#tracker-' + event + '-img').addClass('tracker-shadow');
        } else {
            $('#tracker-' + event + '-img').removeClass('tracker-shadow');
        }
    });
}
