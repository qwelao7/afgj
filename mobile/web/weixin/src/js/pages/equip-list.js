require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-list", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            title = $('#title').html(),
            container = $('#container'),
            header = $('#header');

        var ids = [],
            communitys = [],
            values = [],
            community,
            path;

        /**
         * 加载房产
         */
        function loadCommunitys() {
            common.ajax('GET', '/hcho/auth-address', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    ids = data.id;
                    communitys = data.name;

                    community = (url.id == 0 || !url.id)?ids[0]:url.id;

                    var info = {},
                        index = ids.indexOf(community);
                    info.length = ids.length;

                    console.log(communitys, community);

                    if (ids.length > 1) {
                        info.name = communitys[index] + '▾';

                        $.each(communitys, function(index, item) {
                            values.push(item + '▾');
                        });
                    } else {
                        info.name = communitys[index];
                    }

                    var htm = juicer(title, info);
                    header.prepend(htm);

                    loadData();

                    (ids.length > 1) && picker()
                } else {
                    $.modal({
                        title: '温馨提示', text: '设施管理是小区内认证业主互助共享服务', buttons: [{
                            text: '知道了', onClick: function () {
                                window.history.go(-1);
                            }
                        }, {
                            text: '前往认证', bold: true, onClick: function () {
                                window.location.href = 'estate-manage.html';
                            }
                        }]
                    });
                }
            });
        }

        /**
         * 加载数据
         */
        function loadData() {
            common.ajax('GET', '/facilities/index', {'id': community}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无设施信息!</h3>";
                    container.append(template);
                }
            })
        }

        /**
         * 选择小区
         */
        function picker() {
            $('#picker').picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">请选择房产</h1></header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: values,
                        displayValues: communitys
                    }
                ],
                onClose: function() {
                    var str = $('#picker').val();
                    str = str.replace('▾','');str = $.trim(str);
                    var index = communitys.indexOf(str);
                    community = ids[index];

                    window.location.href = 'equip-list.html?id=' + community;
                }
            })
        }

        /**
         * 创建房产
         */
        $(document).on('click', '#create', function() {
           location.href = 'equip-add-img.html?id=' + community;
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function() {
            location.href = common.ectouchUrl + '&c=user&a=index';
        });

        /**
         * 查看详情
         */
        $(document).on('click', '.equip-detail', function() {
            var self = $(this),
                id = self.data('id');
            
            path = '?id=' + id + '&address=' + community;
            
            location.href = 'equip-detail.html' + path;
        });

        /**
         * 养护信息
         */
        $(document).on('click', '.equip-notice', function () {
            var self = $(this),
                id = self.data('id'),
                len = self.data('len');

            if (len == 0) return false;

            path = '?id=' + id + '&address=' + community;
            
            location.href = 'equip-care-list.html' + path;
        });

        /**
         * 查看不完整信息
         */
        $(document).on('click', '#unfinish', function() {
            location.href = 'equip-unfinish.html?id=' + community;
        });

        loadCommunitys();
        var pings = env.pings;pings();
    });

    $.init();
});
