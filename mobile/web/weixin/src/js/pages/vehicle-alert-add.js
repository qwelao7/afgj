require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#vehicle-alert-add", function (e, id, page) {
        var url = common.getRequest();

        var status = true,
            params = {},
            key,
            transArr = ['name', 'next_km', 'last_km', 'next_month', 'last_date'],
            chineseArr = ['提醒名称', '间隔公里数', '当前公里数', '间隔月份', '上次保养时间'],
            skipArr = ['next_km', 'last_km', 'next_month', 'last_date'];

        /**
         * 控制输入数字
         */
        $(document).on('keyup', 'input[type=number]', function() {
            var _this = $(this),
                reg = _this.val().match(/\d+/),
                txt = '';
            if (reg != null)
            {
                txt = reg[0];
            }
            _this.val(txt);
        });

        $('input[name=next_month]').on('input propertychange', function () {
            var self = $(this),
                val = $.trim(self.val()),
                next = $('input[name=last_date]').parents('.normal-list');

            if (val == '' || val == undefined) {
                next.addClass('hide');
                next.val('');
            } else {
                if (next.hasClass('hide')) {
                    next.removeClass('hide');
                }
            }
        });

        /**
         * 创建车辆提醒
         */
        $('#submit').live('click', function () {
           if (!status) return false;
            status = false;

            params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));
            
            var valid = validate(params);

            if (valid) {
                params['car_id'] = url.id;
                common.ajax('POST', '/vehicle/add-remind', params, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        status = true;
                        location.href = 'vehicle-alert.html?id=' + url.id;
                    } else {
                        $.alert('很抱歉,新建车辆提醒失败,失败原因:' + rsp.data.message, function () {
                            status = true;
                        })
                    }
                })
            }
        });

        /**
         * 返回
         */
        $('#back').on('click', function () {
           location.href = 'vehicle-alert.html?id=' + url.id;
        });

        //验证表单
        function validate(params) {
            //检验参数是否为空
            for(var i in params) {
                if (skipArr.indexOf(i) == -1) {
                    if (params[i] == '' || params[i] == undefined) {
                        key = transArr.indexOf(i);
                        $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败', function() {
                            status = true;
                        });
                        return false;
                    }
                }
            }

            //特殊校验
            //间隔月份和间隔公里数二选一
            if (params['next_km'] == '' && params['next_month'] == '') {
                $.alert('很抱歉,间隔月份和间隔公里数不能同时为空!', '校验失败', function () {
                    status = true;
                });
                return false;
            }
            
            return true;
        }

        var pings = env.pings;pings();
    });

    $.init();
});
