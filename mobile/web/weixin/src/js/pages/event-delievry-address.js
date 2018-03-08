require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';


    $(document).on("pageInit", "#event-delievry-address", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        var params = {
            address_id: 0,
            address: '',
            consignee: '',
            mobile: ''
        };
        var status = true;

        function loadData() {
            common.ajax('get', '/hcho/user-address', {
                'address_id': (url.address_id) ? url.address_id : ''
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    if (data && data.length == 0) {
                        toCreateAddress();
                    } else {
                       var html = juicer(tpl, data);
                        container.append(html);

                        initParams(data);
                    }
                } else {
                    toCreateAddress();
                }
            })
        }
        
        function initParams(data) {
            params.address_id = data.address_id;
            params.address = data.address;
            params.consignee = data.consignee;
            params.mobile = data.mobile;
        }

        function toCreateAddress() {
            $.alert('很抱歉,您还未添加收件地址,请前往添加!', '温馨提示', function () {
                var path = '?event_id=' + url.event_id;

                location.href = 'address-add.html' + path;
            })
        }
        
        function validAddress() {
            if (params.address != '' && params.consignee != '' && params.mobile != '') {
                return true;
            } else {
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
                                var path = '?event_id=' + url.event_id;

                                if (params.address_id == '') {
                                    location.href = 'address-add.html' + path;
                                } else {
                                    path += '&address_id=' + params.address_id;

                                    location.href = 'address-edit.html' + path;
                                }
                            }
                        }
                    ]
                });
                return false;
            }
        }

        function specialStep(applyId) {
            if (url.event_id == 50) {
                $.confirm('甲醛检测仪必须配合测试片使用，您需要在"回来啦平台"购买吗？',
                    function () {
                        var path = '?event_id=' + url.event_id + '&address_id=' + params.address_id + '&apply_id=' + applyId +'&id=1'; // 甲醛测试片商品

                        location.href = 'event-material.html' + path;
                    },
                    function () {
                        paySuccess(applyId);
                    }
                );
            } else {
                paySuccess(applyId);
            }
        }

        function applyEvent() {
            if (!status) return false;
            status = false;

            renderNav('提交中...', true);

            common.ajax('get', '/hcho/save-apply', {
                address_id: params.address_id,
                events_id: url.event_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    specialStep(data);
                } else {
                    $.alert('很抱歉,信息提交失败,请重试!失败原因:' + rsp.data.message, '提交失败', function() {
                        renderNav('下一步', false);

                        status = true;
                    })
                }
            })
        }

        function renderNav(text, during) {
            var template = '<nav class="bar bar-tab next-step"><a class="tab-item external' + ((during) ? ' cancel' : '') + '"><span class="' + ((during) ? ' font-dark' : ' font-white') + '">' + text + '</span></a></nav>';

            $('.next-step').replaceWith(template);
        }

        function paySuccess(applyId) {
            common.ajax('GET', '/hcho/apply-success', {
                apply_id: applyId
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    // 二维码页面
                    var path = '?event_id=' + url.event_id;
                    location.href = 'event-wechat.html' + path;
                }
            })
        }

        $(document).on('click','.next-step', function () {
            if (validAddress()) {
                applyEvent();
            }
        });

        $('.JAddressList').live('click', function () {
            var self = $(this),
                address_id = self.data('address_id'),
                path = '?event_id=' + url.event_id;

            path += (address_id) ? '&address_id=' + address_id : '';

            location.href = 'order-address.html' + path;
        });

        $('#back').on('click', function () {
            location.href = 'event-detail.html?id=' + url.event_id;
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});