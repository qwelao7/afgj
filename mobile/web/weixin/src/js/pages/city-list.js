require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';

    $(document).on('pageInit', '#city-list', function(e, id, page) {
        //参数
        var tpl = $('#tpl').html(),
            list = $('#list').html(),
            container = $('#container');

        function loadData() {
            common.ajax('GET', '/site/city', {}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info;

                    var html = juicer(tpl, data);
                    container.append(html);

                    $('#search').val('');
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无城市信息!</h3>";
                    container.append(template);
                }
            })
        }

        loadData();

        /**
         * 查询
         */
        $(document).on('click', '#submit', function() {
            var str = $('#search').val().trim();

            if(str == '') {
                $.alert('请输入要查询的城市名!');return;
            }

        });

        /**
         * 监听input
         */
        $('#search').on('input propertychange', function () {
            var str = $(this).val().trim();
            if (str == "") {
                container.empty();
                loadData();
            }
        });

        /**
         * 选择城市
         */
        $(document).on('click', '.city', function() {
            var self = $(this),
                id = self.data('id');

            window.location.href = 'community-list.html?id=' + id;
        });

        /**
         * 查询城市
         */
        $(document).on('click', '#submit', function() {
            var val = $('#search').val().trim(),
                self = $(this);

            self.off('click');

            if(val == '') {
                $.alert('请填写要查询的城市名!');
                return;
            }

            common.ajax('GET', '/site/search-city', {'city': val}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info;

                    var html = juicer(list, data);
                    container.empty().append(html);
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无查询的城市信息!</h3>";
                    container.empty().append(template);
                }

            });
        });

        $('#back').live('click', function() {
           var refer = localStorage.getItem('order_address_create');
            if (refer && refer == 'true') {
                location.href = 'order-address.html';
            } else {
                location.href = 'estate-manage.html';
            }
        });

        var pings = env.pings;pings();
    });

    $.init();
})