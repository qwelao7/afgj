require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#login-sms', function (e, id, page) {
        /** 获取本地存储 **/
        var storage = window.localStorage;
        var mobile = storage.getItem('mobile');
        var userMobile = $('#mobile');
        userMobile.html('+86 ' + mobile);

        var sumbitBtn = $('#sumbitBtn');

        var keep,
            timer1, timer2,
            state = true,
            time = $('.sms-text-right');

        var template, tpl;

        /**
         * 发送短信验证码
         */
        function sendCode() {
            $.ajax({
                type: 'POST',
                url: common.WEBSITE_API + '/site/smscaptch',
                data: {mobile: mobile},
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.data.code == 0) {
                        cutdown('sms');
                    } else {
                        $.modal({
                            title: '错误提示',
                            text: '很抱歉,短信验证码发送失败!',
                            buttons: [
                                {
                                    text: '取消绑定',
                                    onClick: function () {
                                        window.location.href = 'personal-info.html';
                                    }
                                },
                                {
                                    text: '重新尝试',
                                    bold: true,
                                    onClick: function () {
                                        sendCode();
                                    }
                                }
                            ]
                        });
                    }
                }
            })
        }

        /**
         * 发送语音验证
         */
        function sendVoice() {
            $.ajax({
                type: 'POST',
                url: common.WEBSITE_API + '/site/voice-code',
                data: {mobile: mobile},
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.data.code == 0) {
                        cutdown('voice');
                    } else {
                        $.modal({
                            title: '错误提示',
                            text: '很抱歉,语音验证码发送失败!',
                            buttons: [
                                {
                                    text: '取消绑定',
                                    onClick: function () {
                                        window.location.href = 'personal-info.html';
                                    }
                                },
                                {
                                    text: '重新尝试',
                                    bold: true,
                                    onClick: function () {
                                        sendVoice();
                                    }
                                }
                            ]
                        });
                    }
                }
            })
        }

        /**
         * 倒计时
         * @param param
         */
        function cutdown(param) {
            keep = 60;

            clearInterval(timer1);
            clearInterval(timer2);
            function tip() {
                keep--;
                time.html(keep + 's');
                if (keep <= 0) {
                    clearInterval(timer1);
                    clearInterval(timer2);
                    if (param == 'sms') {
                        time.html('<a style="color:#009042;" id="repost-sms">重新发送</a>');
                    } else if (param == 'voice') {
                        time.html('<a style="color:#009042;" id="repost-voice">重新发送</a>');
                    }
                }
            }

            if (param == 'sms') {
                timer1 = setInterval(tip, 1000);
            } else if (param == 'voice') {
                timer2 = setInterval(tip, 1000);
            }
        }

        /**
         * 绑定手机
         */
        function bindMobile() {
            var str = $('#code').val().trim();
            if (str == '') {
                $.alert('验证码不能为空,请重新输入');
                return;
            }

            if(!state) return false;

            state = false;

            common.ajax('POST', '/site/bind-mobile', {'mobile': mobile, 'code': str}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    window.localStorage.removeItem('mobile');
                    window.location.href = 'login-success.html';
                } else if (rsp.data.code == 2) {
                    $.alert('输入码错误,请重新输入!', '绑定失败', function() {
                        state = true;
                    });
                } else {
                    state = true;
                    $.alert('很抱歉,手机号绑定失败,请重新输入!', '绑定失败', function() {
                        state = true;
                    });
                }
            });
        }

        /**
         * 重新发送验证码
         */
        $('#repost-sms').live('click', function () {
            var time = $('.sms-text-right');
            time.html('60s');
            sendCode();
        });
        $('#repost-voice').live('click', function () {
            var time = $('.sms-text-right');
            time.html('60s');
            sendVoice();
        });

        /**
         * 监控input的值
         */
        $(document).on('input propertychange', '#code', function () {
            sumbitBtn.removeClass('fog-30');
            sumbitBtn[0].disabled = false;
        });

        /**
         * 提交按钮事件
         */
        $(document).on('click', '#sumbitBtn', function () {
            bindMobile();
        });

        /**
         * 使用语音验证码
         */
        $('#voice').live('click', function () {
            if(!state) return false;

            template = "<h3 class='sms-text-left sms-code'>语音验证码</h3>";
            tpl = "<h3 class='index-login'>收不到语音验证码？？</h3><h3 style='text-align: center;'><span class='index-agreement' id='sms' style='font-size: .7rem;'>使用短信验证码</span></h3>"

            $('.sms-code').replaceWith(template);
            $('.tips-list').empty().append(tpl);
            $('.title').html('请输入语音验证码');

            sendVoice();
        });

        /**
         * 使用短信验证码
         */
        $('#sms').live('click', function () {
            if(!state) return false;

            template = "<h3 class='sms-text-left sms-code'>短信验证码</h3>";
            tpl = "<h3 class='index-login'>收不到短信验证码？</h3><h3 style='text-align: center;'><span class='index-agreement' id='voice' style='font-size: .7rem;'>使用语音验证码</span></h3>";

            $('.sms-code').replaceWith(template);
            $('.tips-list').empty().append(tpl);
            $('.title').html('请输入短信验证码');

            sendCode();
        });

        sendCode();

        var pings = env.pings;pings();

    });

    $.init();

});
