(function ($) {
    'use strict';

    $(function () {
        var $btn       = $('#wp-tuxedo-load-log-btn');
        var $container = $('#wp-tuxedo-log-frame-container');
        var $frame     = $('#wp-tuxedo-log-frame');

        if (!$btn.length || !window.wpTuxedoLog) return;

        $btn.on('click', function () {
            $btn.prop('disabled', true).text('Loading…');

            $.post(wpTuxedoLog.ajaxUrl, {
                action: wpTuxedoLog.action,
                nonce:  wpTuxedoLog.nonce,
            })
            .done(function (response) {
                if (response.success) {
                    // srcdoc set via setAttribute so the browser HTML-decodes the
                    // htmlspecialchars-encoded string back to raw markup before rendering
                    $frame[0].setAttribute('srcdoc', response.data.html);
                    $container.show();
                    $btn.hide();
                } else {
                    $btn.prop('disabled', false).text('Load logs');
                }
            })
            .fail(function () {
                $btn.prop('disabled', false).text('Load logs');
            });
        });
    });

})(jQuery);
