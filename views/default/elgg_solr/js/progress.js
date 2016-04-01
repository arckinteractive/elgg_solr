define(function(require) {
    var elgg = require('elgg');
    var $ = require('jquery');

    var refresh_log = function() {
        elgg.get('ajax/view/elgg_solr/ajax/progress', {
            data: {
                time: $('#solr-progress-results').data('time')
            },
            success: function(result) {
                $('#solr-progress-results span.type').text(result.type);
                $('#solr-progress-results span.typetotal').text(result.typecount);
                $('#solr-progress-results span.indexedtotal').text(result.count);
                $('#solr-progress-results span.percent').text(result.percent + '%');
                $('#solr-progress-results span.querytime').text(result.querytime);
                $('#solr-progress-results span.message').text(result.message);
                $('#solr-progress-results span.logdate').text(result.date);

                if (result.logtime && result.cacheoptions) {
                    var url = elgg.get_site_url() + 'action/elgg_solr/restart_reindex?logtime='+result.logtime;
                    var link = '<a class="elgg-button elgg-button-action elgg-requires-confirmation mhs" href="' + elgg.security.addToken(url) + '">Restart</a>';

                    var stop_url = elgg.get_site_url() + 'action/elgg_solr/stop_reindex?logtime='+result.logtime;
                    var stop_link = '<a class="elgg-button elgg-button-action elgg-requires-confirmation mhs" href="' + elgg.security.addToken(stop_url) + '">Stop</a>';

                    var html = elgg.echo('elgg_solr:reindex:restart', [link+stop_link]);
                    $('#solr-progress-results span.restart').html(html);
                }
                else {
                    $('#solr-progress-results span.restart').html('');
                }

                window.setTimeout(function() { refresh_log(); }, 3000);
            }
        });
    };

    refresh_log();
});