require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#estate-info", function (e, id, page) {

        var tpl = $('#tpl').html(),
            ext = $('#ext').html(),
            tab = $('#tab').html(),
            mark = $('#mark'),
            container = $('#container');

        /** 获取url参数 **/
        var url = common.getRequest();

        /** 自定义函数 **/
        var isDefault = function (data) {
            data = (data == 'yes') ? '是' : '否';
            return data;
        };
        var format = function (data, type) {
            data = data.split(' ');
            if (type == 0) return data[0];
            if (type == 1) return data[1];
        };
        juicer.register('isDefault', isDefault);
        juicer.register('format', format);

        $('.modal-overlay').removeClass('modal-overlay-visible');

        common.ajax('GET', '/house/detail', {id: url.id, type: url.fang}, false, function (rsp) {
            if (rsp.data.code == 0) {
                var data = rsp.data.info;
                if(url.fang == 1) {
                    var html = juicer(tpl,data);
                    container.append(html);
                }else if(url.fang == 2) {
                    var extTpl = juicer(ext, data);
                    container.append(extTpl);
                }
            } else {
                var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无房产信息,请重试!</h3>";
                container.append(template);
            }
        });

        common.ajax('GET', '/estate/index', {id: url.id, type: url.fang}, false, function (rsp) {
            var data = rsp.data;
            auth(data);

            share(data);

            /**
             * render tab
             */
            var htm = juicer(tab, { 'code': data.code });
            mark.append(htm);
        });

        /** 显示下拉菜单 **/
        $(page).on('click', '#popup', function () {
            mark.toggleClass('unshow');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });

        /** 前往编辑房产界面 **/
        $(page).on('click', '#update', function () {
            $('.modal-overlay').toggleClass('modal-overlay-visible');
            window.location.href = 'estate-edit.html?id=' + url.id + '&type=' + url.fang;
        });

        /** 删除房产 **/
        $(page).on('click', '#delete', function () {
            var self = $(this);
            self.prop('disabled', true);

            $('.modal-overlay').toggleClass('modal-overlay-visible');
            $.confirm('确认删除房产?', function () {
                common.ajax('POST', '/house/delete', {id: url.id, type: url.fang}, false, function (rsp) {
                    if (rsp.data.code == 0) {
                        window.location.href = 'estate-manage.html';
                    }else if(rsp.data.code == 117){
                        $.alert('很抱歉,删除失败,失败原因: ' + rsp.data.message, function() {
                            self.prop('disabled', false);
                            mark.toggleClass('unshow');
                        });
                    }else {
                        $.alert('删除失败,请重试',function() {
                            self.prop('disabled', false);
                            mark.toggleClass('unshow');
                        });
                    }
                })
            });
        });

        /** 前往认证界面 **/
        function auth(param) {
            $(page).on('click', '#auth', function () {
                switch (param.code) {
                    case 102:
                        window.location.href = 'estate-auth.html?id=' + url.id + '&type=' + url.fang;
                        break;
                    case 103:
                        $('.modal-overlay').toggleClass('modal-overlay-visible');
                        $.modal({
                            title: '认证提示',
                            text: '很抱歉,您的认证请求被拒绝,拒绝原因: ' + param.info,
                            buttons: [
                                {
                                    text: '知道了',
                                    onClick: function() {
                                        mark.toggleClass('unshow');
                                    }
                                },
                                {
                                    text: '再次认证',
                                    bold: true,
                                    onClick: function () {
                                        window.location.href = 'estate-auth.html?id=' + url.id + '&type=' + url.fang;
                                    }
                                }
                            ]
                        });
                        break;
                    case 104:
                        $('.modal-overlay').toggleClass('modal-overlay-visible');
                        $.alert('您的房产认证进行中,请等待!', function() {
                            mark.toggleClass('unshow');
                        });
                        break;
                    case 0:
                        $('.modal-overlay').toggleClass('modal-overlay-visible');
                        $.alert('您的房产认证已完成!', function() {
                            mark.toggleClass('unshow');
                        });
                        break;
                }
            });
        }

        /**
         * 分享房产
         */
        function share(params) {
            $('#share').live('click', function () {
                if (params.code == 0) {
                    var path = '?id=' + url.id;

                    location.href = 'estate-share.html' + path;
                } else {
                    $('.modal-overlay').toggleClass('modal-overlay-visible');
                    $.alert('很抱歉,请先认证房产!', '温馨提示', function () {
                        mark.toggleClass('unshow');
                    });
                }
            })
        }

        /**
         * 遮罩层
         */
        $(page).on('click', '.modal-overlay', function() {
            var self = $(this);
            self.toggleClass('modal-overlay-visible');
            mark.toggleClass('unshow');
        })

        $('#back').live('click', function() {
            location.href = 'estate-manage.html';
        });

        var pings = env.pings;pings();
    });

    $.init();
});