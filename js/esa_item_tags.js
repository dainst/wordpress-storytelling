(function ($) {
    $.fn.esa_item_tagchecklist = function(options) {

        function colorize(term) {
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
            return "rgba(" + c[0] +", " + c[1] + ", " + c[2] + ", 0.4)";

        }

        function colorizeTags(tagchecklist) {
            tagchecklist.children('li').each(function(index, li) {
                var text = $(li).clone().children().remove().end().text().trim();
                $(li).css('background-color', colorize(text))
            });
        }

        return this.each(function() {
            var tagchecklist = $(this);

            tagchecklist.bind('DOMSubtreeModified', function(e) {
                if (e.target.innerHTML.length > 0) {
                    colorizeTags(tagchecklist);
                }
            });

            colorizeTags(tagchecklist);


        });
    };
}(jQuery));

jQuery(document).ready(function($){
    $('.tagchecklist').esa_item_tagchecklist();
});