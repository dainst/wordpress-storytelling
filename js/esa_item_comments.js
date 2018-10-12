(function ($) {
    $.fn.esa_item_comments = function(options) {
        return this.each(function() {
            var esa_item_show_comments = $(this).find('.esa-item-show-comments-button');
            var esa_item_comments_list = $(this).find('.esa-item-comments-list');
            var esa_item_comment_form = $(this).find('.esa-item-comment-form');

            esa_item_show_comments.on('click', function(e) {
                console.log("!",esa_item_comments_list);
                $(esa_item_comments_list).toggle();
                var expanded = ($(esa_item_show_comments).attr("aria-expanded") === "true");
                $(esa_item_show_comments).attr("aria-expanded", !expanded);
            })
        });
    };
}(jQuery));

jQuery(document).ready(function($){
    $('.esa-item-comments').esa_item_comments();
});