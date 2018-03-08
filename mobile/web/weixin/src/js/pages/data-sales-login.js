require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-sales-login", function (e, id, page) {
        var tpl = $('#login-input').html(),
            skip = $('#skip').html(),
            content = $('.index-content-block'),
            header = $('#header');

        var data = {};
        var url = common.getRequest();
        //渲染模板
        var htm = juicer(tpl, data);
        content.append(htm);
        if (url.type && url.type == 1) {
            var html = juicer(skip, url);
            header.prepend(html);
        }

        var sumbitMobile = $('#submit-mobile'),
            tel = $('#tel'),
            cancel = $('#cancel');

        function validCaptcha(captcha, str) {
            common.ajax('POST', '/site/verifylsm', {
                'captcha': captcha
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    //设置本地存储
                    var storage = window.localStorage;
                    storage.setItem('mobile', str);

                    cancel.hide();
                    tel.prop('disabled', true);

                    validMobile(str);
                } else {
                    $.alert('很抱歉,人机验证失败,请重试!');
                }
            })
        }


        function validMobile(str) {
            $.ajax({
                url: env.ajax_data + "/misc/sms",
                dataType: 'json',
                type: 'POST',
                data: {'mobile': str},
                success: function (rsp) {
                    if (rsp.status == 200) {
                        location.href = "data-login-sms.html";
                    } else {
                        $.alert('很抱歉,'+rsp.msg, function () {
                            tel.prop('disabled', '');
                            cancel.show();
                        });
                    }
                },
                error: function () {
                    $.alert('很抱歉,提交失败,请重试!', function () {
                        tel.prop('disabled', '');
                        cancel.show();
                    });
                },
            });
        }

        /** 监听input的值 **/
        $(page).on('input propertychange', '#tel', function () {
            var result = common.check(tel.val(), 2);
            if (result) {
                sumbitMobile.removeClass('fog-30');
                sumbitMobile.prop('disabled', false);
            } else {
                sumbitMobile.addClass('fog-30');
                sumbitMobile.prop('disabled', true);
            }
        });

        /**
         * 跳转回广场
         */
        $(page).on('click', '#direct', function () {
            window.location.href = common.ectouchPic;
        });

        /** 绑定submit-mobile按钮点击 **/
        $(page).on('click', '#submit-mobile', function () {
            var str = tel.val();

            //人机验证校验
            var captcha = $('#lc-captcha-response').val();
            if (captcha == '') {
                $.alert('请先完成人机验证!', '提交失败');
                return false;
            }

            validCaptcha(captcha, str);
        });

        /** 清空input输入的值tel **/
        $(page).on('click', '#cancel', function () {
            tel[0].value = '';
            sumbitMobile.addClass('fog-30');
        });


        var pings = env.pings;
        pings();
    });

    $.init();
});
