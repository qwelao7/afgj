require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#points-cashier', function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            button = $('#button').html(),
            container = $('#container'),
            status = true,
            exchange_rate,
            total_points,
            pay;

        common.img();

        var template = '<div class="normal-list lr-padding has-border-bottom row" style="margin-bottom:0" id="to-pay">' +
            '<div class="col-33 h3" style="color: #888">友元支付</div> ' +
            '<div class="col-66"> ' +
            '<input type="text" placeholder="请输入金额" style="border: none" name="pay_points" readonly> ' +
            '</div> ' +
            '</div>';

        function loadData() {
            common.ajax('GET', '/cashier/index', {'id': url.id}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data),
                        htm = juicer(button, {});

                    exchange_rate = parseInt(data.payment.exchange_rate);
                    total_points = parseInt(data.payment.avaiable_points); //积分
                    
                    container.append(html);
                    container.after(htm);
                } else {
                    var tips = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";
                    container.append(tips);
                }
            })
        }

        /***
         * input 校验
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

        $(document).on('change', 'input[name=input_pay]', function() {
            var _this = $(this);
            pay = parseInt(_this.val() * 100); //转为分

            $('#to-pay').remove();
            $('#to-pay-cash').remove();

            if ($.trim(_this.val()) == '') {
                pay = '';return;
            }

            if (pay <= total_points) {
                container.append(template);
                $('input[name=pay_points]').val(pay); //显示金额
                pay = pay / 100;
            } else {
                var cash = (pay - total_points) / exchange_rate,
                    tem = '<h3 style="text-align: center" id="to-pay-cash">' +
                        '您还需支付 <span class="font-green">' + cash + '</span> 元现金</h3>';

                container.append(template);
                container.append(tem);
                $('input[name=pay_points]').val(total_points);  //显示金额
                pay = total_points / 100;  //显示金额
            }
        });

        $(document).on('click', '#submit', function() {
            if (!status) return;
            status = false;

            if ($('#to-pay-cash').length > 0) {
                $.modal({
                    title: '友情提示',
                    text: '请确保您已经支付了现金!',
                    buttons: [
                        {
                            text: '去付款',
                            onClick: function() {
                                status = true;
                            }
                        },
                        {
                            text: '确认支付',
                            bold: true,
                            onClick: function () {
                                submit();
                            }
                        }
                    ]
                })
            } else {
                submit();
            }
        });

        function submit() {
            var params = {};
            params.id = url.id;
            params.type = 'points';
            params.money = pay; //显示金额
            params.remark = $.trim($('textarea[name=remarks]').val());

            if (!params.money || params.money == '') {
                $.alert('很抱歉,支付金额不能为空,请填写!', '提交失败');
                status = true;
                return false;
            }

            common.ajax('POST', '/cashier/pay', params, true, function(rsp) {
                if (rsp.data.code == 0) {
                    location.href = 'points-spent.html?id=' + rsp.data.info;
                } else {
                    $.alert('很抱歉,支付失败! ' + rsp.data.message, '支付失败');
                    status = true;
                }
            })
        }

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});