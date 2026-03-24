jQuery(document).ready(function($) {
    $(document).on('click', '.wpts-nav', function() {
        var year = $(this).data('year');
        var month = $(this).data('month');
        var $calendar = $(this).closest('.wpts-calendar');

        $.get(wpts_ajax.ajaxurl, {
            action: 'wpts_navigate',
            year: year,
            month: month
        }, function(html) {
            $calendar.replaceWith(html);
        });
    });

    $(document).on('click', '.wpts-day:not(.wpts-day-empty)', function() {
        var date = $(this).data('date');
        if (!date) return;
        // Toggle day detail (could open modal or expand inline)
        $(this).toggleClass('wpts-day-selected');
    });
});
