/**
 * This script is responsible for the SecSign ID settings for individual users.
 * It allows adding a SecSign ID, changing the ID and enabling or disabling
 * the 2FA.
 * 
 * @author SecSign Technologies Inc.
 * @copyright 2019 SecSign Technologies Inc.
 */
(function (OC, window, $) {
    'use strict';

    let allowEdit = false;

    /**
     * This function saves a given string as the SecSign ID for the current user and
     * updates the UI.
     * @param {string} id 
     */
    function save(id) {
        $.post(OC.generateUrl('/apps/secsignid/id/enable/'), {
                secsignid: id
            },
            function (data) {
                $("#disabled").hide();
                $("#enabled").show();
                $("#disable").html("Disable");
                $("#disable").unbind("click");
                $("#disable").click(function () {
                    disable();
                });
                $("#enabled input").val(data.secsignid);
                $("#description").text("You have already added a SecSign ID protecting your account.")
                $("#enabled div").html("<p class='animated fadeOut' style='color: green'>Successfully updated</p>");
            }
        ).fail(function () {
            alert("Failed to save SecSign ID, try again");
        });
    }

    /**
     * This function disables 2FA for the current user.
     */
    function disable() {
        $.post(OC.generateUrl('/apps/secsignid/id/disable/'), null,
            function (data) {
                $("#enabled div").html("<p class='animated fadeOut' style='color: green'>2FA disabled</p>");
                $("#disable").html("Enable");
                $("#disable").unbind("click");
                $("#disable").click(function () {
                    save($("#secsignid_input_en").val());
                })
                $("#description").text("You have a SecSign ID linked with your account, but 2FA is disabled. Press enable to activate 2FA.")

            }
        ).fail(function () {
            alert("Failed to save SecSign ID, try again");
        });
    }

    $.get(OC.generateUrl('/apps/secsignid/allowEdit/'),
        function (allow) {
            allowEdit = allow;
            /**
             * Gets SecSign ID status for current user from server and updates UI accordingly.
             */
            let URL = OC.generateUrl('/apps/secsignid/ids/current/');
            $.ajax({
                type: "GET",
                url: URL,
                success: function (data) {
                    $(".lds-roller").hide();
                    if (allowEdit) {
                        if (data != null && data.secsignid != null) {
                            $("#enabled").show();
                            $("#secsignid_input_en").val(data.secsignid);
                            if (data.enabled == 0) {
                                $("#description").text("You have a SecSign ID linked with your account, but 2FA is disabled. Press enable to activate 2FA.")
                                $("#disable").html("Enable");
                                $("#disable").click(function () {
                                    save($("#secsignid_input_en").val());
                                });
                            } else {
                                $("#disable").click(function () {
                                    disable();
                                });
                            }
                            $("#change_id").click(function () {
                                save($("#secsignid_input_en").val());
                            });

                        } else {
                            $("#disabled").show();
                            $("#enable_id").click(function () {
                                save($("#secsignid_input_dis").val());
                            });
                        }
                    } else {
                        if (data != null && data.secsignid != null) {
                            $("#noedit_enabled").show();
                            $(".id").append(data.secsignid);
                        } else {
                            $("#noedit_disabled").show();
                        }
                    }

                }
            }).fail(function () {
                console.log("failed");
            });
        });
})(OC, window, jQuery);