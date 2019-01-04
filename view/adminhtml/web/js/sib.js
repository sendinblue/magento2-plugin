require([
    "jquery",
    "jquery/ui",
    'domReady!'
], function($) {
    //<![CDATA[
    $(document).ready(
        function() {
            var token = jQuery("#customtoken").val();
            var id_shop_group = jQuery("#id_shop_group").val();
            var id_shop = jQuery("#id_shop").val();
            var iso_code = jQuery('#iso_code').val();

            $(".keyyes").click(function() {
                if (jQuery(this).val() == 1) {
                    jQuery("#apikeybox").show();
                    //jQuery(".hidetableblock").show();
                    jQuery(".unsubscription").show();
                    jQuery(".listData").show();

                } else {
                    jQuery("#apikeybox").hide();
                    jQuery(".hidetableblock").hide();
                    jQuery(".unsubscription").hide();
                    jQuery(".listData").hide();
                }

            });


            if (!$('#apikeybox').is(':hidden')) {

                $('#sender_order').keyup(function() {
                    var val = $(this).val();
                    if (isInteger(val) || val.length == 0) {
                        $("#sender_order").attr('maxlength', '11');
                        $('#sender_order_text').text((11 - val.length));
                    } else {
                        $("#sender_order").attr('maxlength', '11');
                        $('#sender_order_text').text((11 - val.length));
                    }
                });


                /*----- Function for check list value  ------*/
                $('.submitForm2').on('click', function() {
                    var list = $('#display_list').val();
                    if (!list) {
                        alert('Please choose atleast one list.');
                        return false;
                    }
                });
                /*------ Ends ----------*/

                    $('.manage_subscribe_block input[name=subscribe_confirm_type]').click(function() {

                        $('.manage_subscribe_block .inner_manage_box').slideUp();
                        $(this).parents('.manage_subscribe_block').find('.inner_manage_box').slideDown();

                    });

                    $('.openCollapse').click(function() {

                        if ($(this).is(":checked")) {
                            $(this).parent('.form-group').find('.collapse').slideDown();
                        } else {
                            $(this).parent('.form-group').find('.collapse').slideUp();
                        }
                    });
                

                //doubleoptin alert functionality

                $('#template_doubleoptin').bind('change', function() {
                    var abcs = $(this).find('option:selected').text().toLowerCase().indexOf('double optin');
                    if (abcs === -1) {
                        alert('You must select a template with the tag [DOUBLEOPTIN]');
                        var selectedObj = $('#template_doubleoptin option').filter(function() {
                            return $(this).text().toLowerCase().indexOf('double optin') !== -1
                        });
                        selectedObj.attr('selected', true)
                        $(this).val(selectedObj.val());
                    }
                });
                //  append hidden field in edit personal information page
                window.onload = function() {
                    var newsletter_hidden_val = $('#newsletter').val();
                    $("#newsletter").append('<input type="hidden" id="sendinflag" value="' + newsletter_hidden_val + '" name="sendinflag">');
                };

                $('#sender_order').keyup(function(e) {
                    var str = $(this).val();
                    str = str.replace(/[^a-zA-Z 0-9]+/g, '');
                    $('#sender_order').val(str);
                });
                $('#sender_shipment').keyup(function(e) {
                    var str = $(this).val();
                    str = str.replace(/[^a-zA-Z 0-9]+/g, '');
                    $('#sender_shipment').val(str);
                });
                $('#sender_campaign').keyup(function(e) {
                    var str = $(this).val();
                    str = str.replace(/[^a-zA-Z 0-9]+/g, '');
                    $('#sender_campaign').val(str);
                });

                if ($('#sender_order').val() != '' || $('#sender_order').val() == '') {
                    var val_d = $('#sender_order').val();

                    if (val_d != 'undefine' || isInteger(val_d) || val_d.length == 0) {
                        $("#sender_order").attr('maxlength', '11');
                        //$('#sender_order_text').text((11 - val_d.length));

                    } else {
                        $("#sender_order").attr('maxlength', '11');
                        $('#sender_order_text').text((11 - val_d.length));
                    }
                }

                $("#sender_order").keydown(function(event) {
                    if (event.keyCode == 32) {
                        event.preventDefault();
                    }
                });

                $('#sender_order_message').keyup(function() {
                    var chars = this.value.length,
                        messages = Math.ceil(chars / 160),
                        remaining = messages * 160 - (chars % (messages * 160) || messages * 160);
                    if (remaining == 0) {
                        remaining = 160.
                    }

                    $('#sender_order_message_text').text(remaining);
                    $('#sender_order_message_text_count').text(messages);
                });

                if ($('#sender_order_message').val() != '' || $('#sender_order_message').val() == '') {
                    var chars = $('#sender_order_message').val().length;

                    var messages = Math.ceil(chars / 160);
                    remaining = messages * 160 - (chars % (messages * 160) || messages * 160);
                    if (remaining == 0) {
                        remaining = 160.
                    }
                    $('#sender_order_message_text').text(remaining);
                    $('#sender_order_message_text_count').text(messages);
                }

                $('#sender_shipment').keyup(function() {
                    var val = $(this).val();

                    if (isInteger(val) || val.length == 0) {
                        $("#sender_shipment").attr('maxlength', '11');
                        $('#sender_shipment_text').text((11 - val.length));
                    } else {
                        $("#sender_shipment").attr('maxlength', '11');
                        $('#sender_shipment_text').text((11 - val.length));
                    }
                });

                if ($('#sender_shipment').val() != '' || $('#sender_shipment').val() == '') {
                    //var val = $('#sender_shipment').val();
                    var val = 0;
                    if (isInteger(val) || val.length == 0) {
                        $("#sender_shipment").attr('maxlength', '11');
                        $('#sender_shipment_text').text((11 - val.length));
                    } else {
                        $("#sender_shipment").attr('maxlength', '11');
                        $('#sender_shipment_text').text((11 - val.length));
                    }
                }
                $("#sender_shipment").keydown(function(event) {
                    if (event.keyCode == 32) {
                        event.preventDefault();
                    }
                });

                $('#sender_shipment_message').keyup(function() {
                    var chars = this.value.length,
                        messages = Math.ceil(chars / 160),
                        remaining = messages * 160 - (chars % (messages * 160) || messages * 160);
                    if (remaining == 0) {
                        remaining = 160.

                    }
                    $('#sender_shipment_message_text').text(remaining);

                    $('#sender_shipment_message_text_count').text(messages);

                });

                if ($('#sender_shipment_message').val() != '' || $('#sender_shipment_message').val() == '') {
                    var chars = $('#sender_shipment_message').val().length;
                    var messages = Math.ceil(chars / 160);
                    remaining = messages * 160 - (chars % (messages * 160) || messages * 160);
                    if (remaining == 0) {
                        remaining = 160.

                    }
                    $('#sender_shipment_message_text').text(remaining);

                    $('#sender_shipment_message_text_count').text(messages);
                }

                $('#sender_campaign').keyup(function() {
                    var val = $(this).val();

                    if (isInteger(val) || val.length == 0) {
                        $("#sender_campaign").attr('maxlength', '11');
                        $('#sender_campaign_text').text((11 - val.length));
                    } else {
                        $("#sender_campaign").attr('maxlength', '11');
                        $('#sender_campaign_text').text((11 - val.length));
                    }
                });

                if ($('#sender_campaign').val() != '') {
                    //var val = $('#sender_campaign').val();
                    var val = 0;
                    if (isInteger(val) || val.length == 0) {
                        $("#sender_campaign").attr('maxlength', '11');
                        $('#sender_campaign_text').text((11 - val.length));
                    } else {
                        $("#sender_campaign").attr('maxlength', '11');
                        $('#sender_campaign_text').text((11 - val.length));
                    }
                }
                $("#sender_campaign").keydown(function(event) {
                    if (event.keyCode == 32) {
                        event.preventDefault();
                    }
                });

                $('#sender_campaign_message').keyup(function() {
                    var chars = this.value.length;
                    var messages = Math.ceil(chars / 160);
                    var remaining = messages * 160 - (chars % (messages * 160) || messages * 160);
                    if (remaining == 0) {
                        remaining = 160.
                    }
                    $('#sender_campaign_message_text').text(remaining);
                    $('#sender_campaign_message_text_count').text(messages);
                });

                if ($('#sender_campaign_message').val() != '') {
                    //var chars = $('#sender_campaign_message').val().length,
                    var chars = 0;
                    messages = Math.ceil(chars / 160),
                        remaining = messages * 160 - (chars % (messages * 160) || messages * 160);

                    $('#sender_campaign_message_text').text(remaining);

                    $('#sender_campaign_message_text_count').text(messages);
                }

                $(".sms_order_setting_cls").click(function() {
                    $(this).parents('td').find('.loading-ajax').show();
                    var order_setting_post = jQuery(this).val();
                    var order_setting = $("input[name='sms_order_setting']:checked").val();
                    var type = 'order';
                    var form_key = jQuery("#form_key").val();
                    var file_url = jQuery("#ajaxcontentUrl").val();
                    $.ajax({
                        type: "POST",
                        async: false,
                        url: file_url,
                        data: {
                            "form_key": form_key,
                            "order_setting": order_setting,
                            "order_setting_post": order_setting_post,
                            "type": type
                        },
                        beforeSend: function() {
                            $('#ajax-busy').show();
                        },
                        success: function(msg) {
                            $('#ajax-busy').hide();
                            $('.loading-ajax').hide();
                            if (order_setting == 1) {
                                jQuery(".hideOrder").show();
                            } else {
                                jQuery(".hideOrder").hide();
                            }
                            alert(msg);
                        }
                    });
                });
                $(".sms_shiping_setting_cls").click(function() {
                    $(this).parents('td').find('.loading-ajax').show();
                    var shiping_setting_post = jQuery(this).val();
                    var shiping_setting = $("input[name='sms_shiping_setting']:checked").val();
                    var type = 'shiping';
                    var form_key = jQuery("#form_key").val();
                    var file_url = jQuery("#ajaxcontentUrl").val();
                    $.ajax({
                        type: "POST",
                        async: false,
                        url: file_url,
                        data: {
                            "form_key": form_key,
                            "shiping_setting": shiping_setting,
                            "shiping_setting_post": shiping_setting_post,
                            "type": type
                        },
                        beforeSend: function() {
                            $('#ajax-busy').show();
                        },
                        success: function(msg) {
                            $('#ajax-busy').hide();
                            $('.loading-ajax').hide();
                            if (shiping_setting == 1) {
                                jQuery(".hideShiping").show();
                            } else {
                                jQuery(".hideShiping").hide();
                            }
                            alert(msg);
                        }
                    });

                });

                $(".sms_campaign_setting_cls").click(function() {
                    $(this).parents('td').find('.loading-ajax').show();
                    var campaign_setting_post = jQuery(this).val();
                    var campaign_setting = $("input[name='sms_campaign_setting']:checked").val();
                    var type = 'campaign';
                    var form_key = jQuery("#form_key").val();
                    var file_url = jQuery("#ajaxcontentUrl").val();
                    $.ajax({
                        type: "POST",
                        async: false,
                        url: file_url,
                        data: {
                            "form_key": form_key,
                            "campaign_setting": campaign_setting,
                            "campaign_setting_post": campaign_setting_post,
                            "type": type
                        },
                        beforeSend: function() {
                            $('#ajax-busy').show();
                        },
                        success: function(msg) {
                            $('#ajax-busy').hide();
                            $('.loading-ajax').hide();
                            if (campaign_setting == 1) {
                                jQuery(".hideCampaign").show();
                            } else {
                                jQuery(".hideCampaign").hide();
                            }
                            alert(msg);
                        }
                    });

                });

                if ($('input:radio[name=sms_order_setting]:checked').val() == 0) {
                    $('.hideOrder').hide();
                } else {
                    $('.hideOrder').show();
                }

                $(".Sms_Choice").click(function() {
                    if (jQuery(this).val() == 1) {
                        jQuery(".multiplechoice").hide();
                        jQuery(".singlechoice").show();
                    } else {
                        jQuery(".multiplechoice").show();
                        jQuery(".singlechoice").hide();
                    }
                });

                //date picker function
                $('.datepickercls').datepicker({
                     dateFormat: 'yy-mm-dd'
                });

                jQuery('input:radio[name=Sms_Choice]').click(function() {
                    var getVal = jQuery(this).val();
                    if (getVal == 0) {
                        jQuery(".multiplechoice").show();
                        jQuery(".singlechoice").hide();
                        jQuery(".sib_datepicker").hide();

                    } else if (getVal == 2) {
                        jQuery(".multiplechoice").show();
                        jQuery(".singlechoice").hide();
                        jQuery(".sib_datepicker").show();
                    } else {
                        jQuery(".singlechoice").show();
                        jQuery(".multiplechoice").hide();
                        jQuery(".sib_datepicker").hide();
                    }
                });

                jQuery('#r1_Sms_Choice').attr('checked', true);
                if ($('input:radio[name=sms_credit]:checked').val() == 0)
                    jQuery(".hideCredit").hide();
                else
                    jQuery(".hideCredit").show();


                $(".sms_credit_cls").click(function() {
                    $(this).parents('td').find('.loading-ajax').show();
                    var sms_credit_post = jQuery(this).val();
                    var sms_credit = $('input:radio[name=sms_credit]:checked').val();
                    var type = 'sms_credit';
                    var form_key = jQuery("#form_key").val();
                    var file_url = jQuery("#ajaxcontentUrl").val();
                    $.ajax({
                        type: "POST",
                        async: false,
                        url: file_url,
                        data: {
                            "form_key":form_key,
                            "sms_credit_post":sms_credit_post,
                            "sms_credit": sms_credit,
                            "type": type
                        },
                        beforeSend: function() {
                            $('#ajax-busy').show();
                        },
                        success: function(msg) {
                            $('#ajax-busy').hide();
                            $('.loading-ajax').hide();
                            if (sms_credit == 1) {
                                jQuery(".hideCredit").show();
                            } else {
                                jQuery(".hideCredit").hide();
                            }
                            alert(msg);
                        }
                    });

                });

                if ($('input:radio[name=sms_shiping_setting]:checked').val() == 0) {
                    $('.hideShiping').hide();
                } else {
                    $('.hideShiping').show();
                }

                if ($('input:radio[name=sms_campaign_setting]:checked').val() == 0) {
                    $('.hideCampaign').hide();
                } else {
                    $('.hideCampaign').show();
                }
                
                            $("#selectSmsList").multiselect({
                                header: false,
                                checkall: false
                            });

                $("#tabs li").click(function() {
                    //  First remove class "active" from currently active tab
                    $("#tabs li").removeClass('active');

                    //  Now add class "active" to the selected/clicked tab
                    $(this).addClass("active");

                    //  Hide all tab content
                    $(".tab_content").hide();

                    //  Here we get the href value of the selected tab
                    var selected_tab = $(this).find("a").attr("href");

                    //  Show the selected tab content
                    $(selected_tab).fadeIn();

                    //  At the end, we add return false so that the click on the link is not executed
                    return false;
                });

            }

        jQuery('#showUserlist').click(function(){
            if(jQuery('.userDetails').is(':hidden'))
            {
                loadData(1);
                jQuery('#Spantextless').show();
                jQuery('#Spantextmore').hide();
            }else
            {
                jQuery('#Spantextmore').show();
                jQuery('#Spantextless').hide();
            }
            jQuery('.userDetails').slideToggle();
         });

           jQuery("#display_list").multiselect({
                 header: false,
                 checkall: false
            });



            $(".ord_track_cls_btn").click(function() {
                $(this).parents('td').find('.loading-ajax').show();
                var ord_track_btn = jQuery(this).val();
                var ord_track_status = $("input[name='ord_track_status']:checked").val();
                var form_key = jQuery("#form_key").val();
                var file_url = jQuery("#ajaxcontentUrl").val();
                $.ajax({
                    type: "POST",
                    async: false,
                    url: file_url,
                    data: {
                        "form_key": form_key,
                        "ord_track_status": ord_track_status,
                        "ord_track_btn": ord_track_btn
                    },
                    beforeSend: function() {
                        $('#ajax-busy').show();
                    },
                    success: function(msg) {

                        $('#ajax-busy').hide();
                        $('.loading-ajax').hide();
                        alert(msg);
                    }
                });
            });

            $(".smtptestclickcls").click(function() {
                $(this).parents('td').find('.loading-ajax').show();
                var smtp_post = jQuery(this).val();
                var smtps_tatus = $("input[name='smtpservices']:checked").val();
                var form_key = jQuery("#form_key").val();
                var file_url = jQuery("#ajaxcontentUrl").val();
                if (smtptest == 0) {
                    $('#smtptest').hide();
                }
                if (smtptest == 1) {
                    $('#smtptest').show();
                }
                $.ajax({
                    type: "POST",
                    async: false,
                    url: file_url,
                    data: {
                        "form_key":form_key,
                        "smtps_tatus": smtps_tatus,
                        "smtp_post": smtp_post
                    },
                    beforeSend: function() {
                        $('#ajax-busy').show();
                    },
                    success: function(msg) {

                        $('#ajax-busy').hide();
                        $('.loading-ajax').hide();
                        alert(msg);
                    }
                });
            });

            var radios = $('input:radio[name=managesubscribe]:checked').val();

            if (radios == 0) {
                $('.managesubscribeBlock').hide();
            } else {
                $('.managesubscribeBlock').show();
            }

            $(".managesubscribecls").click(function() {
                $(this).parents('td').find('.loading-ajax').show();
                var submitButton = jQuery(this).val();
                var managesubscribe = $("input[name='managesubscribe']:checked").val();
                var form_key = jQuery("#form_key").val();
                var file_url = jQuery("#ajaxcontentUrl").val();

                if (managesubscribe == 0) {
                    $('.managesubscribeBlock').hide();
                }
                if (managesubscribe == 1) {
                    $('.managesubscribeBlock').show();
                }
                $.ajax({
                    type: "POST",
                    async: false,
                    url : file_url,
                    data: {
                        "managesubscribe": managesubscribe,
                        "form_key": form_key,
                        "manageSubsVal": submitButton
                    },
                    beforeSend: function() {
                        $('#ajax-busy').show();
                    },
                    success: function(msg) {
                        $('#ajax-busy').hide();
                        $('.loading-ajax').hide();
                        alert(msg);
                    }
                });
            });
            //select multiple list for file name
            $("#oem_list")
                .change(function() {
                    var str = "";
                    var count = ($("#oem_list option:selected").length - 1);
                    $("#oem_list option:selected").each(function(i, val) {

                        str += $(this).text();

                        if (i < count) {
                            str += ',';
                        }
                    });
                    $("#em_text_val").val(str);
                })
                .change();

            //hide and show order import tab
            $(".ord_track_cls_btn").click(function() {
                var tracktest = $("input[name='ord_track_status']:checked").val();
                if (tracktest == 0) {
                    $('#ordertrack').hide();
                }
                if (tracktest == 1) {
                    $('#ordertrack').show();
                }
            });
            $("#importOrderTrack").click(function() {
                $(this).parents('td').find('.loading-ajax').show();
                var order_import_post = jQuery('#importact').val();
                var ord_track_status = $('input:radio[name=ord_track_status]:checked').val();
                var form_key = jQuery("#form_key").val();
                var file_url = jQuery("#ajaxcontentUrl").val();

                $.ajax({
                    type: "POST",
                    async: false,
                    url: file_url,
                    data: {
                        "form_key": form_key,
                        "order_import_post": order_import_post,
                        "ord_track_status": ord_track_status
                    },
                    beforeSend: function() {
                        $('#ajax-busy').show();
                    },
                    success: function(msg) {
                        $('#ajax-busy').hide();
                        $('#ordertrack').hide();
                        $('.loading-ajax').hide();
                        alert(msg);
                    }
                });
            });

            $('.testOrdersmssend').on('click', function() {
                var order_send_post = jQuery(this).val();
                var sender = $('#sender_order').val();
                var message = $('#sender_order_message').val();
                var number = $('#sender_order_number').val();
                var sendererr = $('#senderfield').val();
                var mobileerr = $('#smsfield').val();
                var messageerr = $('#messagefield').val();
                var form_key = jQuery("#form_key").val();
                var file_url = jQuery("#ajaxcontentUrl").val();

                if (sender == '') {
                    alert(sendererr);
                    document.getElementById('sender_order').focus();
                    return false;
                } else if (message == '') {
                    alert(messageerr);
                    document.getElementById('sender_order_message').focus();
                    return false;
                } else if (number == '') {
                    alert(mobileerr);
                    document.getElementById('sender_order_number').focus();
                    return false;
                }

                $.ajax({
                    type: "POST",
                    async: false,
                    url: file_url,
                    data: {
                        "form_key": form_key,
                        "sender": sender,
                        "message": message,
                        "number": number,
                        "order_send_post": order_send_post
                    },
                    beforeSend: function() {
                        $('#sender_order_submit').next('.loading-ajax').css({
                            'display': 'inline-block'
                        });
                        $('#ajax-busy').show();
                    },
                    success: function(msg) {
                        $('#ajax-busy').hide();
                        $('.loading-ajax').hide();
                        alert(msg);
                    }
                });
                return false;
            });

            $('.testSmsShipped').on('click', function() {
                var shipped_send_post = $(this).val();
                var sender = $('#sender_shipment').val();
                var message = $('#sender_shipment_message').val();
                var number = $('#sender_shipment_number').val();
                var sendererr = $('#senderfield').val();
                var mobileerr = $('#smsfield').val();
                var messageerr = $('#messagefield').val();
                var form_key = $("#form_key").val();
                var file_url = $("#ajaxcontentUrl").val();

                if (sender == '') {
                    alert(sendererr);
                    document.getElementById('sender_shipment').focus();
                    return false;
                } else if (message == '') {
                    alert(messageerr);
                    document.getElementById('sender_shipment_message').focus();
                    return false;
                } else if (number == '') {
                    alert(mobileerr);
                    document.getElementById('sender_shipment_number').focus();
                    return false;
                }

                $.ajax({
                    type: "POST",
                    async: false,
                    url: file_url,
                    data: {
                        "form_key": form_key,
                        "sender": sender,
                        "message": message,
                        "number": number,
                        "shipped_send_post": shipped_send_post
                    },
                    beforeSend: function() {
                        $('#sender_shipment_submit').next('.loading-ajax').css({
                            'display': 'inline-block'
                        });
                        $('#ajax-busy').show();
                    },
                    success: function(msg) {
                        $('.loading-ajax').hide();
                        $('#ajax-busy').hide();
                        alert(msg);
                    }
                });
                return false;
            });

            $('.testSmsCampaignsend').on('click', function() {
                var campaign_test_submit = $(this).val();
                var sender = $('#sender_campaign').val();
                var message = $('#sender_campaign_message').val();
                var number = $('#sender_campaign_number_test').val();
                var sendererr = $('#senderfield').val();
                var mobileerr = $('#smsfield').val();
                var messageerr = $('#messagefield').val();
                var form_key = jQuery("#form_key").val();
                var file_url = jQuery("#ajaxcontentUrl").val();

                if (sender == '') {
                    alert(sendererr);
                    document.getElementById('sender_campaign').focus();
                    return false;
                } else if (message == '') {
                    alert(messageerr);
                    document.getElementById('sender_campaign_message').focus();
                    return false;
                } else if (number == '') {
                    alert(mobileerr);
                    document.getElementById('sender_campaign_number_test').focus();
                    return false;
                } else {
                    $.ajax({
                        type: "POST",
                        async: false,
                        url: file_url,
                        data: {
                            "form_key": form_key,
                            "sender": sender,
                            "message": message,
                            "number": number,
                            "campaign_test_submit": campaign_test_submit
                        },
                        beforeSend: function() {
                            $('#sender_campaign_test_submit').next('.loading-ajax').css({
                                'display': 'inline-block'
                            });
                            $('#ajax-busy').show();
                        },
                        success: function(msg) {
                            $('.loading-ajax').hide();
                            $('#ajax-busy').hide();
                            alert(msg);
                        }
                    });
                }
                return false;
            });

            $('.sender_order_save').on('click', function() {
                var senderfield = $('#senderfield').val();
                var messagefield = $('#messagefield').val();
                var sender = $('#sender_order').val();
                var message = $('#sender_order_message').val();
                if (sender == '') {
                    alert(senderfield);
                    document.getElementById('sender_order').focus();
                    return false;
                } else if (message == '') {
                    alert(messagefield);
                    document.getElementById('sender_order_message').focus();
                    return false;
                }
            });
            $('.sender_shipment_save').on('click', function() {
                var senderfield = $('#senderfield').val();
                var messagefield = $('#messagefield').val();
                var sender = $('#sender_shipment').val();
                var message = $('#sender_shipment_message').val();
                if (sender == '') {
                    alert(senderfield);
                    document.getElementById('sender_shipment').focus();
                    return false;
                } else if (message == '') {
                    alert(messagefield);
                    document.getElementById('sender_shipment_message').focus();
                    return false;
                }
            });

            $('.sender_campaign_save').on('click', function() {
                var smsfield = $('#smsfield').val();
                if ($('.singlechoice').is(":visible")) {
                    $('.fortestsms').hide();
                    var singlechoice = $('#singlechoice').val();
                    if (singlechoice == '') {
                        alert(smsfield);
                        document.getElementById('singlechoice').focus();
                        return false;
                    }
                }
                var senderfield = $('#senderfield').val();
                var messagefield = $('#messagefield').val();
                var sender = $('#sender_campaign').val();
                var message = $('#sender_campaign_message').val();
                if (sender == '') {
                    alert(senderfield);
                    document.getElementById('sender_campaign').focus();
                    return false;
                } else if (message == '') {
                    alert(messagefield);
                    document.getElementById('sender_campaign_message').focus();
                    return false;
                }
            });

            jQuery('body').on('click', '.ajax_contacts_href', function (e) {
               jQuery(this).parent('td').append('<div class="loader-contact"></div>');
                var email = jQuery(this).attr('email');
                var status = jQuery(this).attr('status');
                var ajaxUrl = jQuery("#ajaxcontentUrl").val();
                var form_key = jQuery("#form_key").val();
                var contact_subs = 'contact_subs';

                jQuery.ajax({
                        type : "POST",
                        async : false,
                        url : ajaxUrl,
                        data : {
                            "form_key": form_key,
                            "email": email,
                            "newsletter": status,
                            "contact_subs": contact_subs
                        },
                        beforeSend : function() {
                            $('.midleft').next('.loading-ajax').css({
                            'display': 'inline-block'
                        });
                        $('#ajax-busy').show();
                        },
                        success : function(msg) {
                            jQuery('.loading-ajax').hide();            
                        }
                    });

                var page_no = jQuery('#pagenumber').val();

                loadData(page_no); // For first time page load      
            });

            function loadData(page) {
                var form_key = jQuery("#form_key").val();
                var file_url = jQuery("#ajaxcontentUrl").val();
                var contact_data = 'contact_load';
                $.ajax({
                    type: "POST",
                    async: false,
                    url: file_url,
                    data: {
                        "page": page,
                        "form_key": form_key,
                        "contact_data": contact_data
                    },
                    beforeSend: function() {
                        $('#showUserlist').next('.loading-ajax').css({
                            'display': 'inline-block'
                        });
                        $('#ajax-busy').show();
                    },
                    success: function(msg) {
                        $('.loading-ajax').hide();
                        $(".midleft").html(msg);
                        $(".midleft").ajaxComplete(
                            function(event, request, settings) {
                                $(".midleft").html(msg);
                            });
                        return true;
                    }
                });
            }

            //loadData(1, token); // For first time page load
            // default
            // results

            jQuery('body').on('click',' .pagination li.active',function() {
                var page = jQuery(this).attr('p');
                jQuery('#pagenumber').val(page);
                loadData(page);
            });

            $('.toolTip').on('mouseover mouseout', function(e) {
                var title = $(this).attr('title');
                var offset = $(this).offset();

                if (e.type == 'mouseover') {
                    $('body').append(
                        '<div id="tipkk" style="top:' +
                        offset.top +
                        'px; left:' +
                        offset.left +
                        'px; ">' + title +
                        '</div>');
                    var tipContentHeight = $('#tipkk')
                        .height() + 25;
                    $('#tipkk').css(
                        'top',
                        (offset.top - tipContentHeight) +
                        'px');
                } else if (e.type == 'mouseout') {
                    $('#tipkk').remove();
                }
            });


            /*------- Ashutosh 10Aug 2016 ------*/

            if ($('input[name=status]:checked').val() == 1 && $('input#apikeys').val() != '') {
                $('#left-part').addClass('right-opened');
                $('#right-part').show();
            } else {
                $('#left-part').removeClass('right-opened');
                $('#right-part').hide();
            }


            /*---- Display related tab when form submit ---*/

            $('.main-tabs a').click(function(){
                var getFullPath = window.location.href;
                var getHash = getFullPath.split('#');
                getDataId = $(this).attr('data-id');
                if(getHash[1]){
                    window.location = getHash[0]+getDataId;
                }else{
                    window.location = window.location.href+getDataId;
                }
            })

            var getFullPath = window.location.href;
            var getHash = getFullPath.split('#');
            if(getHash[1]){
                $('.main-tabs-content').find('.tab-pane').removeClass('active');
                $('.main-tabs-content').find('#'+getHash[1]).addClass('active');
                $('.main-tabs a').removeClass('active');
                $('.main-tabs a#'+getHash[1]).addClass('active');
                /*--- Work when found # in URL -------*/
                $('#tabs a').click(function(){
                    var getTabID = $(this).attr('href');
                    $('#tabs li').removeClass('active');
                    $(this).parent('li').addClass('active');
                    $("#tab1, #tab2, #tab3").css("display","none");
                    $(getTabID).css("display","block");
                    $('#tabs_content_container .tab_content').fadeOut();
                    $('#tabs_content_container '+getTabID).fadeIn();
                });
           }else{
                $('.main-tabs-content').find('#about-sendinblue').addClass('active');
                $('.main-tabs a#about-sendinblue').addClass('active'); 
            }      
        /*---- Ends --- Display related tab when form submit ---*/

            $('input[name=status]#n').click(function() {
                setTimeout(function() {
                    $('#left-part').removeClass('right-opened');
                }, 500);
                $('#right-part').hide();
            });

            $('input[name=status]#y').click(function() {
                setTimeout(function() {
                    $('#left-part').addClass('right-opened');
                }, 500);
                $('#right-part').show();
                $('.tableblock').show();
            });


            /*--- For new design tabs-------*/
            $('.main-tabs a').click(function() {
                var get_id = $(this).attr('data-id');

                $('.main-tabs-content .tab-pane.active').removeClass('active');
                $('.main-tabs-content ' + get_id).addClass('active');

                $('.main-tabs a.active').removeClass('active');
                $(this).addClass('active');

            });
        });

    function isNormalInteger(str) {
        return /^\+?(0|[0-9]\d*)$/.test(str);
    }

    function RegexEmail(email) {
        var emailRegexStr = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
        var isvalid = emailRegexStr.test(email);
        return isvalid;
    }

    function validate(emailerr, limiter) {

        if (document.notify_sms_mail_form.notify_email.value == "" || RegexEmail(document.notify_sms_mail_form.notify_email.value) == false) {
            alert(emailerr);
            document.notify_sms_mail_form.notify_email.focus();
            return false;
        }
        if (document.notify_sms_mail_form.notify_value.value <= 0 ||
            isNormalInteger(document.notify_sms_mail_form.notify_value.value) == false) {
            alert(limiter);
            document.notify_sms_mail_form.notify_value.focus();
            return false;
        }

        return (true);
    }

    // get site base url

    function isInteger(val) {
        var numberRegex = /^[+-]?\d+(\.\d+)?([eE][+-]?\d+)?$/;
        if (numberRegex.test(val)) {
            return true
        }
        return false;
    }
    //]]>
});