(function ($) {
    // Simple wrapper around jQuery animate to simplify animating progress from your app
    // Inputs: Progress as a percent, Callback
    // TODO: Add options and jQuery UI support.
    $.fn.animateProgress = function (progress, callback) {
        return this.each(function () {
            $(this).animate({
                width: progress + '%'
            }, {
                duration: 3000,
                easing: 'swing'
            });
        });
    };

})(jQuery);