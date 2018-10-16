(function ($) {
    $.fn.esa_item_comments = function(options) {

        function isTrue(value) {
            return (["on", "On", "true", true, 1, "1"].indexOf(value) !== -1);
        }

        return this.each(function() {

            var esa_item_show_comments = $(this).find('.esa-item-comments-button.show-comments');
            var esa_item_show_form = $(this).find('.esa-item-comments-button.show-form');
            var esa_item_comments_list = $(this).find('.esa-item-comments-list');
            var esa_item_comment_form = $(this).find('.esa-item-comments-form');
            //var esa_change_page_btns = $(this).find('.esa-item-comments-button.change-page');
            var wrapper_id = $(this).data("esa-item-wrapper-id");

            var current_tab = "";

            function get_comments(page) {
                page = page || 0;
                console.log("get comment page ", page);
                jQuery.post(
                    window.ajaxurl,
                    {
                        'action': 'esa-comment-list',
                        'esa_item_wrapper_id': wrapper_id,
                        'page': page
                    })
                    .done(function(response) {
                        $(esa_item_comments_list).html(response);
                    })
                    .fail(function(err) {
                        console.warn("Comment couldn't be fetched: ", err);
                    });
            }

            function toggle_buttons(tab) {
                $(esa_item_show_comments).attr("aria-expanded", 'false');
                $(esa_item_show_form).attr("aria-expanded", 'false');
                if (tab === current_tab) {
                    $(esa_item_comments_list).toggle(false);
                    $(esa_item_comment_form).toggle(false);
                    current_tab = "";
                    return;
                }
                if (tab === "form") {
                    $(esa_item_comments_list).toggle(false);
                    $(esa_item_comment_form).toggle(true);
                    $(esa_item_show_form).attr("aria-expanded", 'true');
                }
                if (tab === "list") {
                    $(esa_item_comments_list).toggle(true);
                    $(esa_item_comment_form).toggle(false);
                    $(esa_item_show_comments).attr("aria-expanded", 'true');
                    get_comments();
                }
                current_tab = tab;
            }

            esa_item_show_comments.on('click', function(e) {
                toggle_buttons("list")
            });
            esa_item_show_form.on('click', function(e) {
                toggle_buttons("form")
            });
            $(this).on('click', '.esa-item-comments-button.change-page', function(e) {
                console.log(this);
                console.log(e);
                get_comments($(this).data("esa-comment-page"));
            });
            console.log(esaItemCommentsOptions);
            if (isTrue(esaItemCommentsOptions.tab_list_open)) {
                toggle_buttons("list");
            } else if (isTrue(esaItemCommentsOptions.tab_form_open)) {
                toggle_buttons("form");
            }

        });
    };
}(jQuery));

jQuery(document).ready(function($){
    $('.esa-item-comments').esa_item_comments();
});