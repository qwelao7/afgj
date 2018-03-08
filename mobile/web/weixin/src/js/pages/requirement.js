require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#requirement", function (e, id, page) {
        var template = '<span style="font-size:.5rem;background-color: #d92424;color: #ffeae6;padding: .1rem .5rem;line-height: .3rem;border-radius: 11px;">这道题必须回答哦</span>',
            htm = '<span style="font-size:.5rem;background-color: #d92424;color: #ffeae6;padding: .1rem .5rem;line-height: .3rem;border-radius: 11px;">很抱歉,您的电话号码格式错误,请重新输入!</span>',
            state = true;

        /**
         * 选择城市
         */
        $("#area_name").cityPicker({
            toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker font-white">确定</button>\
                                <h1 class="title font-white">请选择城市</h1>\
                                </header>',
            onClose: function() {
                $('#area_name').prev('span').remove();
            }
        });

        /**
         * 选择装修类型
         */
        $("#decorate_type").picker({
            toolbarTemplate: '<header class="bar bar-nav">\
                              <button class="button button-link pull-right close-picker font-white">确定</button>\
                              <h1 class="title font-white">请选择装修类型</h1>\
                              </header>',
            cols: [
                {
                    textAlign: 'center',
                    values: ['毛坯房装修', '旧房出新', '精装修房改造', '房屋维修']
                }
            ],
            onClose: function() {
                $('#decorate_type').prev('span').remove();
            }
        });

        /**
         * 选择户型
         */
        $("#house_type").picker({
            toolbarTemplate: '<header class="bar bar-nav">\
                              <button class="button button-link pull-right close-picker font-white">确定</button>\
                              <h1 class="title font-white">请选择户型</h1>\
                              </header>',
            cols: [
                {
                    textAlign: 'center',
                    values: ['1室', '2室', '3室', '4室', '5室', '6室']
                },
                {
                    textAlign: 'center',
                    values: ['1厅', '2厅','3厅','4厅','5厅','6厅']
                },
                {
                    textAlign: 'center',
                    values: ['1卫', '2卫', '3卫', '4卫', '5卫', '6卫']
                }
            ],
            onClose: function() {
                $('#house_type').prev('span').remove();
            }
        });

        /**
         * 监听input输入
         */
        $(document).on('blur', '.input-change', function () {
            var self = $(this),
                str = self.val();
            if(str != '' || str != null || str != undefined) {
                self.prev('span').remove();
            }
        });

        /**
         * 监听验证
         */
        $(document).on('blur', '.input-tel', function() {
            var self = $(this),
                str = self.val();

            if(!common.check(str, 2) && !common.check(str, 3)) {
                $('#cust_phone').before(htm);
            }else {
                $('#cust_phone').prev('span').remove();
            }
        });


        /**
         * 提交
         */
        $(document).on('click', '#submit', function () {
            if(!state) return;

            state = false;

            var data = {},
                validate = true;
            data.area_name = $('#area_name').val();
            data.community_name = $('#community_name').val();
            data.decorate_type = $('#decorate_type').val();
            data.house_area = $('#house_area').val();
            data.house_type = $('#house_type').val();
            data.cust_name = $('#cust_name').val();
            data.cust_phone = $('#cust_phone').val();

            $('span').remove();

            $.each(data, function(index, item) {
                if(item == '' || item == null || item == undefined) {
                    $('#'+ index).before(template);
                    validate = false;
                    state = true;
                }
            });

            if(!validate) return;

            common.ajax('POST', '/require/index', {'data': data}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    window.location.href = 'requirement-success.html';
                }else {
                    state = true;
                    $.alert('很抱歉,提交失败,请重试!');
                }
            });
        })

        var pings = env.pings;pings();
    });

    $.init();
});
