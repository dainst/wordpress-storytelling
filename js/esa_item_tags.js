(function ($) {
    $.fn.esa_item_tags = function(options) {

        function colorize(term) {

            var masterColor = [false, false, false];

            var t = term.split("").map(function(char) {
                c = char.toUpperCase().charCodeAt(0);
                return (c >= 65 && c <= 90) ? 256 - Math.round((c - 64) * 9.5) : char.charCodeAt(0);
            });
            var c = [];
            var i = 0;
            while (c.length < 3) {
                c.push(t[i++ % t.length]);
            }
            for (i = 0; i < t[0] % 3; i++) {
                c.push(c.shift());
            }
            var color = [
                masterColor[0] ? masterColor[0] : c[0],
                masterColor[1] ? masterColor[1] : c[1],
                masterColor[2] ? masterColor[2] : c[2]
            ];
            return "rgba(" + color[0] + ", " + color[1] + ", " + color[2] + ", 0.4)";

        }

        function colorizeTags(tagchecklist) {
            tagchecklist.children('li').each(function(index, li) {
                $(li).css('background-color', colorize(getTagText(li)));
            });
        }

        function setTagToolTips(tagchecklist) {
            tagchecklist.children('li').each(function(index, li) {
                $(li).attr('title', getTagText(li));
            });
        }

        function getTagText(tag) {
            return $(tag).clone().children().remove().end().text().trim();
        }

        function updateTags(mutationsList, observer) {

            var tags = [];
            this.tagchecklist.children('li').each(function(index, li) {
                tags.push(getTagText(li));
            });

            jQuery.post(
                window.ajaxurl,
                {
                    'action': 'update-esa-tags',
                    'esa_item_wrapper_id':   this.wrapperId,
                    'tags': tags
                })
                .done(function(response) {
                    console.log('The server responded: ', response);
                })
                .fail(function(err) {
                    console.warn("Tags couln't be updated: ", err);
                });

            colorizeTags(this.tagchecklist);
            setTagToolTips(this.tagchecklist);

        }

        return this.each(function() {
            var tagchecklist = $(this).find(".tagchecklist");
            var observer = new MutationObserver(updateTags.bind({
                tagchecklist: tagchecklist,
                container: this,
                wrapperId: $(this).data("esa-item-wrapper-id")
            }));
            observer.observe(tagchecklist.get(0), {attributes: false, childList: true, subtree: true});
            colorizeTags(tagchecklist);
            setTagToolTips(tagchecklist);
        });
    };
}(jQuery));

jQuery(document).ready(function($){
    $('.esa-item-tags').esa_item_tags();
});