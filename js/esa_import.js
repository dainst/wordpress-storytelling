var esa_i = {

    selected_ds: false,
    status: "idle",
    states: ["idle", "running", "error", "finished", "aborted"],

    select_ds: function(e) {

        console.log("select", this.value);

        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'esa_get_ds_form',
                esa_ds: this.value
            },
            success: function(response) {
                jQuery('#esa-input-form').html(response);
            },
            error: function(exception) {
                console.warn(exception);

            }
        });

    },

    toggle_start_btn: function() {
        jQuery('#esa-import-start').attr('disabled', !jQuery(this).attr('checked'));
    },

    import_next_page: function(page) {

        if (esa_i.status !== "running") {
            return;
        }

        jQuery('[name="esa_ds_page"]').val(page);

        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: jQuery('#esa_search_form').serialize() + "&esa_ds_navigation=next&action=esa_import_next_page",
            success: function(response) {

                response = JSON.parse(response);

                esa_i.log(response.message, response.success);

                if (response.success) {
                    if (response.results) {
                        esa_i.import_next_page(page + 1);
                    } else {
                        esa_i.update_status("finished");
                    }
                } else {
                    esa_i.update_status("error");
                }
            },
            error: function(exception) {
                console.warn(exception);
                esa_i.log(exception, false);
                esa_i.update_status("error");
            }
        });
    },

    start_stop_import: function() {
        if (esa_i.status !== "running") {
            esa_i.update_status("running");
            esa_i.import_next_page(0);
        } else {
            esa_i.update_status("aborted");
        }
    },


    update_status: function(status) {
        if (esa_i.states.indexOf(status) === -1) {
            console.error("Unknown state:", status);
            status = "error";
        }

        esa_i.status = status;

        var startStopBtn = jQuery('#esa-import-start');
        var statusView = jQuery('#esa-import-status');

        if (status === "error") {
            startStopBtn.toggle(false);
            statusView.text("Error");
        }

        if (status === "aborted") {
            startStopBtn.toggle(false);
            statusView.text("Aborted by user");
        }

        if (status === "idle") {
            startStopBtn.toggle(true);
            startStopBtn.text("Start");
            statusView.text("");
        }

        if (status === "running") {
            startStopBtn.toggle(true);
            startStopBtn.text("Abort");
            statusView.text("Import Running");
        }

        if (status === "finished") {
            startStopBtn.toggle(false);
            statusView.text("Import Finished");
        }
    },

    log: function(msg, success) {
        var entry = jQuery("<li>" + msg + "</li>");
        entry.css('color', success ? 'green' : 'red');
        jQuery('#esa-import-log').append(entry);

    }



};

jQuery(document).ready(function() {
    jQuery('body').on('change', '#esa-select-datasource',  esa_i.select_ds);
    jQuery('body').on('click', '#esa-import-copyright',  esa_i.toggle_start_btn);
    jQuery('body').on('click', '#esa-import-start',  esa_i.start_stop_import);

});