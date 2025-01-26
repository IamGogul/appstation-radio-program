var $ = jQuery.noConflict();
;(function($, window, document) {
    $.fn.fetch_week_programs = function(){
        return this.each(function(){
            let $this = $(this);

            let $parent = $this.parents(".apps-week-view-calendar-wrap"),
                $title = $parent.find(".apps-week-view-calendar-title"),
                $calendar = $parent.find(".apps-week-view-calendar"),
                $next_btn = $parent.find(".apps-week-view-calendar-next-nav"),
                $prev_btn = $parent.find(".apps-week-view-calendar-prev-nav");

            $this.on("click",function(e){
                e.preventDefault();
                let $week = $this.attr("data-week")

                $.ajax({
                    type      : 'POST',
                    dataType  : 'json',
                    url       : appStation.ajaxUrl,
                    data      : {
                        action: 'fetch_week_view',
                        week  : $week,
                    },
                    beforeSend: function(){
                        $this.attr("disabled","disabled");
                    },
                    success   : function ($res) {
                        if($res.success) {
                            $title.html($res.data.title);
                            $calendar.html( $res.data.html );
                            $next_btn.attr("data-week", $res.data["next-week"]);
                            $prev_btn.attr("data-week", $res.data["prev-week"]);
                        }

                        setTimeout(function() {
                            $this.removeAttr("disabled");
                        }, 1000);
                    },
                });
            });
        });
    };
    $(document).ready(function(){
        $(".apps-week-view-calendar-prev-nav").fetch_week_programs();
        $(".apps-week-view-calendar-next-nav").fetch_week_programs();
    });
})(jQuery, window, document);