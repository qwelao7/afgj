require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#estate-edit', function (e, id, page) {
        var url = common.getRequest();

        var container = $('#container'),
            tpl = $('#tpl').html();

        var status = true;

        //自定义模板函数
        var isDefault = function(data) {
            var bool = (data == 'yes')?true:false;
            return bool;
        };
        juicer.register('isDefault', isDefault);

        function loadData() {
            common.ajax('GET', '/house/update-desc', {id: url.id, type: url.type}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data.type = url.type;

                    var html = juicer(tpl, data);
                    container.append(html);
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无房产信息,请重试!</h3>";
                    container.append(template);
                }
            })
        }

        loadData();

        /**
         * 提交表单
         */
        $(document).on('click', '#submit', function() {
            if (!status) return false;
            status = false;

            var self = $(this),
                frmHouse = {};

            frmHouse.is_default = $('.default').prop('checked') ? 'yes' : 'no';
            frmHouse.consignee = $('#contact').val().trim();
            frmHouse.mobile = $('#mobile').val().trim();

            if (frmHouse.mobile !== '' && frmHouse.mobile != undefined) {
                //手机号 电话号码验证
                if (common.check(frmHouse.mobile, 2) == false && common.check(frmHouse.mobile, 3) == false) {
                    $.alert('您输入的联系电话格式有误','编辑失败', function () {
                        status = true;
                    });
                    return;
                }
            }

            common.ajax('POST', '/house/update', {id: url.id, frmHouse: frmHouse, type: url.type}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    $.alert('房产编辑成功!', '编辑成功', function () {
                        location.href = 'estate-info.html?id=' + url.id + '&fang=' + url.type;
                    })
                }else {
                    $.alert('很抱歉,房产编辑失败!失败原因:' + rsp.data.message, '编辑失败', function() {
                        status = true;
                    })
                }
            });

        });

        $(document).on('click', '#back', function() {
            location.href = 'estate-info.html?id=' + url.id + '&fang=' + url.type;
        });
        
        var pings = env.pings;pings();
    });

    $.init();
});