require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#gift-exchange-address', function (e, id, page) {
        var url = common.getRequest();

        var transArr = ['contact', 'mobile', 'region', 'address'],
            cnArr = ['联系人', '联系电话', '收货地址', '详细地址'],
            status = true,
            key;

        /**
         * cityPicker
         */
        $("#city-picker").cityPicker({
            toolbarTemplate: '<header class="bar bar-nav">\
                              <button class="button button-link pull-right close-picker">确定</button>\
                              <h1 class="title">选择收货地址</h1>\
                              </header>'
        });

        /**
         * 提交
         * @type {module.exports.pings}
         */
        $('#submit').on('click', function() {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            if (validate(params)) {
                /**
                 * 是否为默认收货地址
                 */
                params['desc'] = getRegionDesc(params['region']);
                delete params['region'];

                createAddress(params);
            }
        });

        /**
         * 校验函数
         * @param params
         * @returns {boolean}
         */
        function validate(params) {
            // 检验参数是否为空
            for (var i in params) {
                key = transArr.indexOf(i);
                if (key != -1) {
                    if (params[i] == '' || params[i] == undefined) {
                        $.alert('很抱歉,' + cnArr[key] + '不能为空!', '验证失败', function () {
                            status = true;
                        });
                        return false;
                    }
                }
            }

            // 特殊校验
            if (!common.check(params.mobile, 2)) {
                $.alert('很抱歉,请填写正确的手机号!', '验证失败', function() {
                    status = true;
                });
                return false;
            }

            return true;
        }

        function createAddress(params) {
            params.type=url.type;
            params.qr_code=url.code;
            common.ajax('POST', '/spring/add-address', {
                'data': params
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    location.href='gift-address-success.html?id='+rsp.data.info;
                } else {
                    $.alert('礼券兑换失败!若有疑问，请联系客服电话:13382037834', function () {
                        status = true;
                    })
                }
            })
            console.log(params)
        }

        function getRegionDesc (data) {
            data = $.trim(data);
            data = data.split(' ');
            return data[data.length - 1];
        }

        var pings = env.pings;
        pings();
    });

    $.init();
});