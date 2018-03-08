require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#freeride-3q", function (e, id, page) {
        var url = common.getRequest();
        var loupanId = window.localStorage.getItem('loupanId');
        if (!loupanId) loupanId = 0;

        //参数
        var info = $('#info'),
            data = {},
            status = true;

        var tpl = $('#tpl').html(),
            driver = $('#driver');

        //加载数据
        function loadData() {
            common.ajax('GET', '/ride-sharing/thank-info', {'id': url.id}, false, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    driver.append(html);

                    $('.col-3q-freeride').eq(3).children().addClass('point-active');
                }else {
                    $.alert('很抱歉,暂无相关数据,请重试!', function() {
                        window.history.go(-1);
                    })
                }
            })
        }

        //选择积分
        $(document).on('click', '.col-3q-freeride', function () {
            var self = $(this),
                point = $('#point'),
                val = self.find('p').text();

            self.siblings().children().removeClass('point-active');
            self.children().toggleClass('point-active');

            point.text(val);
        });

        //自定义积分
        $(document).on('click', '.prompt-ok', function () {
            var modal = $.modal({
                'title': '赠送友元',
                'text': '<input type="num" name="input_pay" id="send" style="border: 1px solid #ddd;">',
                'buttons': [
                    {
                        text: '取消'
                    },
                    {
                        text: '确定',
                        onClick: function() {
                            var str = $('#send').val(),
                                point = $('#point');
                            point.text(str);
                            $('.col-3q-freeride').siblings().children().removeClass('point-active');
                        }
                    },
                ]
            })
        });

        /**
         * 限制输入金额
         */
        $(document).on('keyup', 'input[name=input_pay]', function() {
            var _this = $(this),
                reg = _this.val().match(/\d+\.?\d{0,2}/),
                txt = '';
            if (reg != null)
            {
                txt = reg[0];
            }
            _this.val(txt);
        });
        
        /**
         * freeride-3q 提交
         */
        $('#submit').on('click', function () {
            if (!status) return false;
            status = false;

            var self = $(this),
                val = info.val(),
                point = $('#point'),
                points = point.text();

            points = $.trim(points);

            if (points == '0'  || points == '') {
                $.alert('很抱歉,感谢的友元不能为空!', '感谢失败');
                status = true;
                return;
            }

            data.rs_id = url.id;
            data.thanks_word = val;
            data.thanks_point = points;

            common.ajax('POST', '/ride-sharing/thanks', {'data': data}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('友元赠送成功!', '感谢成功', function () {
                        if(url.back == 1) {
                            window.location.href = 'freeride-list.html?id=' + loupanId;
                        }else {
                            window.location.href = 'freeride-detail.html?id=' + url.id;
                        }
                    })
                } else if (rsp.data.code == 100) {
                    status = true;
                    $.alert(rsp.data.message + ', 当前友元数为' + rsp.data.info.pay_points + ', 请重新选择友元数额!', '感谢失败');
                } else if (rsp.data.code == 101) {
                    status = true;
                    $.alert(rsp.data.message, '感谢失败');
                }
            })
        });

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
