require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-3q", function (e, id, page) {
        var url = common.getRequest();

        //参数
        var info = $('#info'),
            data = {},
            status = true;


        var tpl = $('#tpl').html(),
            title = $('#title').html(),
            creater = $('#creater');

        //加载数据
        function loadData() {
           common.ajax('GET', '/events/events-thanks-detail', {
               'id': url.id
           }, true, function (rsp) {
               if (rsp.data.code == 0) {
                   var data = rsp.data.info,
                       html = juicer(tpl, data),
                       tem = juicer(title, data);

                   creater.append(html);
                   $('header').prepend(tem);

                   $('.col-3q-freeride').eq(3).children().addClass('point-active');
               } else {
                   $.alert('很抱歉,暂无相关数据,请重试!', '数据错误', function() {
                       window.history.go(-1);
                   })
               }
           })
        }

        //选择友元
        $(document).on('click', '.col-3q-freeride', function () {
            var self = $(this),
                point = $('#point'),
                val = self.find('p').text();

            self.siblings().children().removeClass('point-active');
            self.children().toggleClass('point-active');

            point.text(val);
        });

        //自定义友元
        $(document).on('click', '.prompt-ok', function () {
            var modal = $.modal({
                'title': '赠送友元',
                'text': '<input type="num" id="send" name="input_pay" style="border: 1px solid #ddd;">',
                'buttons': [
                    {
                        text: '取消'
                    },
                    {
                        text: '确定',
                        onClick: function () {
                            var point = $('#point'),
                                str = $('#send').val();
                            point.text(str);
                            $('.col-3q-freeride').siblings().children().removeClass('point-active');
                        }
                    },
                ]
            })
        });

        /**
         * 控制输入金额
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
         * 提交
         */
        $('#submit').on('click', function () {
            if (!status) return false;
            status = true;

            var self = $(this),
                val = info.val(),
                point = $('#point'),
                points = point.text();

            val = $.trim(val);
            points = $.trim(points);

            if (points == '0' || points == '') {
                $.alert('很抱歉,感谢的友元不能为空', '感谢失败');
                status = true;
                return;
            }

            data.id = url.id;
            data.thanks_word = val;
            data.thanks_point = points;

            common.ajax('POST', '/events/events-thanks', {'data': data}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('友元赠送成功!', '感谢成功', function () {
                        var path = 'event-detail.html?id=' + url.id + '&type=4';

                        window.location.href = path;
                    })
                } else if (rsp.data.code == 100) {
                    $.alert(rsp.data.message + ', 当前友元数为' + rsp.data.info.pay_points + ', 请重新选择友元数额!', '感谢失败');
                    status = true;
                } else {
                    $.alert(rsp.data.message, '感谢失败');
                    status = true;
                }
            })
        });

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
