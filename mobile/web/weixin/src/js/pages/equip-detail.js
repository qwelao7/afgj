require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-detail", function (e, id, page) {
        var community = window.localStorage.getItem('equip_cid');

        var url = common.getRequest();
        /**自定义模板**/
        common.img();
        /**定义变量**/
        var tpl = $('#tpl').html(),
            container = $('#container'),
            brand,
            model,
            state = true;

        var path;

        /**加载数据**/
        function loadData() {
            common.ajax('GET', '/facilities/detail', {'id': url.id}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    container.append(html);
                    brand = data.name;
                    model = data.model;
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据!</h3>";
                    container.append(template);
                }
            });
        }

        /**点击tag标签**/
        $(document).on('click', '#tag', function () {
            $('#popup').css('display', 'block');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });
        $(document).on('click', '.modal-overlay', function () {
            $('#popup').css('display', 'none');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });

        // 点击删除按钮的confirm事件，具体删除数据和跳转事件待添加
        $(document).on('click', '.delete-confirm', function () {
            $('#popup').css('display', 'none');

            if (!state) return;
            state = false;

            $.confirm('您确认删除' + brand + model + '吗?', function () {
                common.ajax('GET', '/facilities/delete', {'id': url.id}, false, function (rsp) {
                    if (rsp.data.code == 0) {
                        window.location.href = 'equip-list.html?id=' + rsp.data.info;
                    }
                });
            }, function () {
                state = true;
            });
        });
        //编辑事件
        $(document).on('click', '.modify', function () {
            path = '?id=' + url.id + '&address=' + url.address;

            window.location.href = 'equip-edit.html' + path;
        });

        /**
         * 养护
         */
        $('.care').on('click', function () {
            path = '?id=' + url.id + '&address=' + url.address;
            path += '&refer=detail';

            location.href = 'equip-care-list.html' + path;

        });

        /**
         * 维修
         */
        $('.repair').on('click', function () {
            path = '?id=' + url.id + '&address=' + url.address;
            location.href = 'equip-repair-list.html' + path;
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function () {
            path = '?id=' + url.address;

            window.location.href = 'equip-list.html' + path;
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});
