var $ = jQuery.noConflict();
;(function($, window, document) {
    $.fn.apps_date_field = function(){
        return this.each(function(){
            let $this     = $(this),
                $fields   = $this.find("input"),
                $settings = {
                    'allowInput'   : true,
                    'enableTime'   : false,
                    'enableSeconds': false,
                    'dateFormat'   : 'm/d/Y',
                    'minDate' : 'today',
                    onReady        : function( selectedDates, dateStr, instance) {
                        $(instance.calendarContainer).addClass('apps-rp-date-flatpickr');
                    },
                    onChange: function( selectedDates, dateStr, instance) {
                        if ( $(instance.element).attr('data-type') === 'start-date' ) {
                            $fields.last().get(0)._flatpickr.set( 'minDate', selectedDates[0] );
                        } else {
                            $fields.first().get(0)._flatpickr.set( 'maxDate', selectedDates[0] );
                        }
                    },
                };

            $fields.each(function(){
                $(this).flatpickr($settings);
            });
        });
    };

    $.fn.apps_time_field = function(){
        return this.each(function(){
            let $this     = $(this),
                $fields   = $this.find("input.rp-time-field-js"),
                $settings = {
                    'allowInput'   : true,
                    'noCalendar'   : true,
                    'enableTime'   : true,
                    'enableSeconds': false,
                    'dateFormat'   : "h:i K",
                    'time_24hr'    : false,
                    onValueUpdate  : function (selectedDates, dateStr, instance) {
                        const $hiddenInput = $(instance.element).next('input[type="hidden"]');
                        if( $hiddenInput ) {
                            const time24 = flatpickr.formatDate(selectedDates[0], "H:i");
                            $hiddenInput.val( time24 );
                        }
                    },
                    onReady        : function(selectedDates, dateStr, instance) {
                        $(instance.calendarContainer).addClass('apps-rp-time-flatpickr');
                    },
                };

            $fields.each(function(){
                $(this).flatpickr($settings);
            });
        });
    };

    $(document).ready(function(){
        $(".rp-date-fields-js").apps_date_field();
        $(".rp-time-fields-js").apps_time_field();
    });
})(jQuery, window, document);