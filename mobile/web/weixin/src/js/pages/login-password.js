require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';

    $(document).on("pageInit", "#login-password", function(e, id, page) {
        var password = $('#password'),
            submit = $('#submit');
        
        /** 获取本地存储 **/
        var storage = window.localStorage;
        var mobile  = storage.getItem('mobile');
        var userMobile = $('#mobile');
        userMobile.html('+86 ' + mobile);

        /** 提交事件 **/
        $(page).on('click', '#submit', function() {
            var str = password.attr('value');
            if(str == undefined) {
                $.alert('请输入你的登录密码!');
                return;
            }
            $.ajax({
                type:'POST',
                url: common.WEBSITE_API + '/site/login',
                data: {mobile: mobile,password:str},
                dataType: 'json',
                success: function(rsp) {
                    if(rsp.data.code == 0) {
                        window.location.href="login-success.html"
                    }else {
                        $.alert('登录密码输入错误,请重新输入!');
                        str[0].value = '';
                    }
                },
                error: function(xhr, type) {
                    console.log(type);console.log(xhr);
                    $.alert('很抱歉,服务器失去联系,请等待...');
                }
            })
        });

        /** 监控input的值 **/
        $(page).on('input propertychange', '#password', function() {
            submit.removeClass('fog-30');
            submit[0].disabled = false;
        });
        var pings = env.pings;pings();
    });

    $.init();
    
});