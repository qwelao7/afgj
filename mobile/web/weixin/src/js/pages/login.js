require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#login", function (e, id, page) {
        var tpl = $('#login-input').html(),
            skip = $('#skip').html(),
            content = $('.index-content-block'),
            header = $('#header');

        var data = {};
        var url = common.getRequest();
        //渲染模板
        var htm = juicer(tpl, data);
        content.append(htm);
        if(url.type && url.type == 1) {
            var html = juicer(skip,url);
            header.prepend(html);
        }

        var sumbitMobile = $('#submit-mobile'),
            tel = $('#tel'),
            indexInput = $('.index-input'),
            geetest_gtserver = 0,
            geetest_user_id = "test",
            cancel = $('#cancel'),
            tips = $('.tips');

        function validCaptcha(captcha, str) {
            common.ajax('POST', '/site/verifylsm', {
                'captcha': captcha
            }, true, function(rsp) {
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
            common.ajax('POST', '/site/signupmobile', {
                'mobile': str
            }, true, function(rsp){
                if (rsp.data.code == 0) {
                    location.href = "login-sms.html";
                }else if(rsp.data.code == 1){
                    $.alert('很抱歉,您输入的手机号已存在,请重试!', function() {
                        tel.prop('disabled', '');
                        cancel.show();
                    });
                }else {
                    $.alert('很抱歉,提交失败,请重试!',function() {
                        tel.prop('disabled', '');
                        cancel.show();
                    });
                }
            })
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
        $(page).on('click', '#direct', function() {
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

        /** 打开关闭弹出层 **/
        $(page).on('click', '.open-agreement', function () {
            $.popup('.popup-agreement');
        });
        $(page).on('click', '.close-popup', function () {
            $.closeModal('.popup-agreement');
        });

        var pings = env.pings;pings();
    });

    $.init();
});
