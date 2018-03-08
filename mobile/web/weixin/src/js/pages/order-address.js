require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    /**
     * 两个入口 区别
     * order-confirm
     * event 有event_id
     */
    $(document).on("pageInit", "#order-address", function (e, id, page) {
        var url = common.getRequest();

        var list = $('#list').html(),
            bottom = $('#bottom').html(),
            container = $('#container');

        var addressId,
            path,
            params = {},
            validate = {},
            hasDefaultCheck = false;

        var checkedHtml = '<i class="iconfont icon-xz-hll font-green check-icon" style="padding: 0;margin: 1rem auto"></i>',
            uncheckedHtml = '<i class="iconfont icon-dz_hll check-icon" style="font-size: 1.2rem;padding: 0;margin: 1rem auto"></i>',
            content = '<div class="sm-margin white" id="checked_address"></div>';

        function loadData() {
            common.ajax('GET', '/order/address-list', {'address_id': url.address_id}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    setParams(data);
                    var html = juicer(list, params);
                    container.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,你暂未添加收货地址!</h3>";
                    container.append(template);
                }
                var htm = juicer(bottom, {});
                container.after(htm);
            })
        }

        function setParams(data) {
            if ((url.address_id == '' || url.address_id == undefined)
                && (data.address[0]['is_default'] == 'no')) {
                params['defaultCheck'] = {};
            } else {
                params['defaultCheck'] = data.address.shift();
                hasDefaultCheck = true;
                addressId = params['defaultCheck']['address_id'];
            }
            params['list'] = data.address;
        }

        /**
         * 选择地址
         * @param self (zepto元素)
         */
        function checkAddress(self) {
             var valid = validAddress(self);

            if (valid) {
                if (hasDefaultCheck) {
                    var wrapper = $('#checked_address'),
                        checkedAddress = wrapper.children('.address_item');

                    checkedAddress.find('i.check-icon').replaceWith(uncheckedHtml);
                    $('#address_list').append(checkedAddress);
                    self.find('i.check-icon').replaceWith(checkedHtml);
                    wrapper.append(self);
                } else {
                    self.find('i.check-icon').replaceWith(checkedHtml);
                    container.prepend($(content).append(self));
                    hasDefaultCheck = true;
                }

                addressId = self.data('address_id');
                $('#container').scrollTop(0);

                goBack();
            }
        }

        /**
         * 检验地址是否完整
         */
        function validAddress(self) {
            validate['consignee'] = $.trim(self.find('.address_consignee').text());
            validate['mobile'] = $.trim(self.find('.address_mobile').text());

            for(var i in validate) {
                if (validate[i] == '' || validate[i] == undefined) {
                    $.modal({
                        title: '收货信息不完整',
                        text: '当前收货信息不完整,马上去完善吧。',
                        buttons: [
                            {
                                text: '知道了'
                            },
                            {
                                text: '确认',
                                bold: true,
                                onClick: function () {
                                    var id = self.data('address_id');

                                    location.href = 'address-edit.html?address_id=' + id;
                                }
                            }
                        ]
                    });
                    return false;
                }
            }
            return true;
        }

        /**
         * 跳转 (选择跳转, 返回跳转)
         */
        function goBack() {
            path = (addressId != '' && addressId != undefined) ? '?address_id=' + addressId : '';
            if (url.event_id && url.event_id != 0) {
                path += '&event_id=' + url.event_id;

                location.href = 'event-delievry-address.html' + path;
            } else {
                location.href = 'order-confirm.html' + path;
            }
        }

        /**
         * 返回
         */
        $('#back').live('click', function() {
            goBack();
        });

        /**
         * 选择收货地址
         */
        $(document).on('click', '.address_item', function(e) {
            var _self = $(this),
                parent = _self.parent();

            if (parent.attr('id') == '#checked_address') {
                return false;
            } else {
                checkAddress(_self);
            }
        });

        /**
         * 跳转编辑
         */
        $('.address_edit').live('click', function(e) {
            e.stopPropagation();

            var self = $(this),
                parent = self.parent(),
                id = parent.data('address_id');

            var path = '?address_id=' + id;
            if (url.event_id && url.event_id != 0) {
                path += '&event_id=' + url.event_id;
            }

            location.href = 'address-edit.html' + path;
        });

        /**
         * 新建收货地址
         */
        $('#addAddress').live('click', function() {
            path = (url.address_id) ? '?address_id=' + url.address_id : '';

            if (url.event_id && url.event_id != 0) {
                path += '&event_id=' + url.event_id;
            }

            location.href = 'address-add.html' + path;
        });

        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});
