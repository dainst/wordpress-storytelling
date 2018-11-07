(function ($) {
    $.fn.esa_item_tags = function(options) {

        function colorize(term) {

            var masterColor = esaItemTagsOptions.color;

            var t = term.split("").map(function(char) {
                c = char.toUpperCase().charCodeAt(0);
                if (c >= 65 && c <= 90) {
                    return 256 - Math.round((c - 64) * 9.5)
                } else if (char.charCodeAt(0) > 255) {
                    return char.charCodeAt(0) % 255;
                } else {
                    return char.charCodeAt(0);
                }
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
                (masterColor.red !== -1)    ? masterColor.red   : c[0],
                (masterColor.green !== -1)  ? masterColor.green : c[1],
                (masterColor.blue !== -1)   ? masterColor.blue  : c[2]
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

        function setTagLinks(tagchecklist, tagslist) {
            tagchecklist.children('li').each(function(index, li) {
                var name = encodeURIComponent(getTagText(li));
                //console.log("tag", name, tagslist[name]);
                var url = (typeof tagslist[name] !== "undefined")
                    ? tagslist[name].url
                    : '/?tag=' + name;
                //console.log("url", url);
                $(li).on('click', function() {
                    window.location.href = url;
                })
            });
        }

        function getTagText(tag) {
            return $(tag).clone().children().remove().end().text().trim();
        }

        function updateTags(tagchecklist, wrapperId, update) {

            var tags = [];
            tagchecklist.children('li').each(function(index, li) {
                tags.push(getTagText(li));
            });

            jQuery.post(
                window.ajaxurl,
                {
                    'action': update ? 'esa-update-tags' : 'esa-get-tags',
                    'esa_item_wrapper_id': wrapperId,
                    'tags': tags
                })
                .done(function(response) {
                    console.log('The server responded: ', JSON.parse(response));
                    setTagLinks(tagchecklist, JSON.parse(response));
                })
                .fail(function(err) {
                    console.warn("Tags couln't be updated: ", err);
                });
            colorizeTags(tagchecklist);
            setTagToolTips(tagchecklist);

        }

        return this.each(function() {
            var tagchecklist = $(this).find(".tagchecklist");
            var wrapperId = $(this).data("esa-item-wrapper-id");
            var observer = new MutationObserver(function(mutationsList, observer) {
                updateTags(tagchecklist, wrapperId, true);
            });
            observer.observe(tagchecklist.get(0), {attributes: false, childList: true, subtree: true});
            updateTags(tagchecklist, wrapperId, false);
        });
    };
}(jQuery));

jQuery(document).ready(function($){
    $('.esa-item-tags').esa_item_tags();
});