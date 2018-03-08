require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';


    $(document).on("pageInit", "#address-edit", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        var transArr = ['contact', 'mobile', 'region', 'address'],
            cnArr = ['联系人', '联系电话', '收货地址', '详细地址'],
            status = true,
            key;

        function loadData() {
            common.ajax('GET', '/house/shipping-address-detail', {
                'id': url.address_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    container.append(html);

                    initRender(data);
                    renderPicker();
                } else {
                    $.alert('很抱歉,获取数据失败,请添加收货地址!', '数据错误', function () {
                        location.href = 'address-add.html';
                    })
                }
            });
        }

        function initRender (data) {
            if (data['is_default'] == 1) {
                $('input[name=is_default]').prop('checked', true);
            } else {
                $('input[name=is_default]').prop('checked', false);
            }
        }

        /**
         * cityPicker
         */
        function renderPicker() {
            $("#city-picker").cityPicker({
                toolbarTemplate: '<header class="bar bar-nav">\
                              <button class="button button-link pull-right close-picker">确定</button>\
                              <h1 class="title">选择收货地址</h1>\
                              </header>'
            });
        }

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

        function getRegionDesc (data) {
            data = $.trim(data);
            data = data.split(' ');
            return data[data.length - 1];
        }

        function edit(params) {
                common.ajax('POST', '/house/update-shipping-address', {
                'data': params
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('编辑收货地址成功!', '编辑成功', function () {
                        var path = '?address_id=' + url.address_id;
                        if (url.refer && url.refer != '') {
                            location.href = 'order-confirm.html' + path; // 订单确认页面返回
                        } else {
                            if (url.event_id && url.event_id != '') {
                                path += '&event_id=' + url.event_id; // 带活动参数的收货地址列表页返回
                            }

                            location.href = 'order-address.html' + path;
                        }
                    })
                } else {
                    $.alert('很抱歉,编辑收货地址失败!失败原因:' + rsp.data.message, '编辑失败', function () {
                        status = true;
                    })
                }
            })
        }

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
                params['is_default'] = ($('input[name=is_default]').prop('checked')) ? 1 : 0;
                params['desc'] = getRegionDesc(params['region']);
                params['id'] = url.address_id;
                delete params['region'];

                edit(params);
            }
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});