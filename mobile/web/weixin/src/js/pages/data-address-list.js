require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-address-list", function (e, id, page) {
        var url = common.getRequest(),
            tpl = $('#tpl').html(),
            header = $('#header').html(),
            menus = $('#menus'),
            menus1 = $('#menus1'),
            select_group = $('#groups').html(),
            select_building = $('#buildings').html(),
            title = $('#title'),
            content = $('#content'),
            choose = $('#choose').html(),
            tab_select = $('#tab_select');
        var values = [],
            communitys = [],
            ids = [],
            data,
            groups = [],
            buildings = [],
            info = {},
            group_name = '',
            building_num = '';

        var token = '';
        token = url.token ? url.token : common.getCookie('openid');

        // 跨域ajax请求

        function loadCommunity() {
            if (url.group == '' || url.group == null || url.group == undefined) {
                group_name = '所有组团';
            } else {
                group_name = url.group;
            }
            if (url.building == '' || url.building == null || url.building == undefined) {
                building_num = '所有楼栋';
            } else {
                building_num = url.building;
            }
            $.ajax({
                url: env.ajax_data + "/pes/house?token=" + token + "&projectCode=" + url.id + '&groupName=' + group_name + '&buildingNum=' + building_num,
                dataType: 'json',
                success: function (rsp) {
                    window.localStorage.removeItem('data_owner');
                    data = rsp.data;
                    console.log(data);
                    $.each(data.query.project, function (index, item) {
                        communitys.push(item);
                        ids.push(index);
                        values.push(item + '▾');
                    });

                    if (url.id == 0 || url.id == '' || url.id == null || url.id == undefined) {
                        info.name = communitys[0];
                        info.id = ids[0];
                        // window.location.href = 'data-address-list.html?id=' + info.id;

                    } else {
                        var index = ids.indexOf(url.id);
                        info.id = url.id;
                        info.name = communitys[index];
                    }

                    var html = juicer(header, info);
                    title.prepend(html);

                    picker();
                    loadData();
                    loadBuilding();
                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            });

        };


        function loadBuilding() {
            var list = [],
                data1 = {};
            $.each(data.list, function (index, item) {
                list[index] = item;
            });
            data1.list = list;
            var template = juicer(tpl, data1);
            content.append(template);
        }

        /**
         * 选择楼盘
         */
        function picker() {
            $("#picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">请选择小区</h1></header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: values,
                        displayValues: communitys
                    }
                ],
                onOpen: function () {
                    var template = "<div class='modal-overlay modal-overlay-visible'></div>";
                    $('.page').append(template);
                },
                onClose: function () {
                    $(".modal-overlay").removeClass('modal-overlay-visible');
                    var str = $('#picker').val();
                    str = $.trim(str);
                    info.name = communitys[values.indexOf(str)];
                    info.id = ids[values.indexOf(str)];
                    window.localStorage.setItem('data_projectId', info.id);
                    window.location.href = 'data-address-list.html?id=' + info.id + '&group=&building=';
                }
            });
        }

        // 加载数据
        function loadData() {
            console.log(data.query.group);
            $.each(data.query.group, function (index, item) {
                if (item == false) {
                    groups.push('所有组团');
                } else {
                    groups = item;
                }
            });
            $.each(data.query.building, function (index, item) {
                buildings = item;
            });

            var select_data = {};
            select_data.groups = groups;
            select_data.buildings = buildings;
            console.log(select_data);
            // 选择组团楼栋的弹出列表
            var html = juicer(select_group, select_data),
                htm = juicer(select_building, select_data);
            menus.append(html);
            menus1.append(htm);
            // 组团楼栋的固定点击框
            var select_show = {};
            select_show.group = group_name;
            select_show.building = building_num;
            var htl = juicer(choose, select_show);
            tab_select.append(htl);
        }

        /**
         * 跳转编辑页面
         */
        $(document).on('click', '.account-item', function () {
            var self = $(this),
                id = self.data('id'),
                detail = self.data('detail');
            window.localStorage.setItem('data_detail', detail);
            window.location.href = 'data-family.html?id=' + id;
        });


        /**
         * 筛选组团
         */
        $(document).on('click', '.group-select', function () {
            menus.toggleClass('visibility-hidden');
            menus1.addClass('visibility-hidden');
        });
        // 筛选楼栋
        $(document).on('click', '.building-select', function () {
            menus1.toggleClass('visibility-hidden');
            menus.addClass('visibility-hidden');
        });

        // 选择组团
        $(document).on('click', '.data-select-groups', function () {
            var self = $(this),
                id = self.data('id'),
                pjCode = '';
            if (url.id == '' || url.id == null || url.id == undefined) {
                pjCode = data.filter.projectCode;
            } else {
                pjCode = url.id;
            }
            if (id == 0) {
                window.location.href = 'data-address-list.html?id=' + pjCode;
            } else {
                window.location.href = 'data-address-list.html?id=' + pjCode + '&group=' + groups[id];
            }
        });
        // 选择楼栋
        $(document).on('click', '.data-select-buildings', function () {
            var self = $(this),
                id = self.data('id'),
                pjCode;
            if (url.id == '' || url.id == null || url.id == undefined) {
                pjCode = data.filter.projectCode;
            } else {
                pjCode = url.id;
            }
            if (url.group == '' || url.group == null || url.group == undefined) {
                if (id == 0) {
                    window.location.href = 'data-address-list.html?id=' + pjCode;
                } else {
                    window.location.href = 'data-address-list.html?id=' + pjCode + '&building=' + buildings[id];
                }
            } else {
                if (id == 0) {
                    window.location.href = 'data-address-list.html?id=' + pjCode + '&group=' + url.group;
                } else {
                    window.location.href = 'data-address-list.html?id=' + pjCode + '&group=' + url.group + '&building=' + buildings[id];
                }
            }
        });


        /**
         * 点击遮罩层
         */
        $(document).on('click', '.modal-overlay', function () {
            $(this).removeClass('modal-overlay-visible');
            $(".picker").picker("close");
            $('#modal-right').addClass('visibility-hidden');
        });


        $(document).on('click', '#edit', function () {
            $('#modal-right').toggleClass('visibility-hidden');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });


        $(document).on('click', '.access-apply', function () {
            window.location.href = 'data-input-auth.html';
        });

        $(document).on('click', '.statistics', function () {
            window.location.href = 'data-statistics.html?id=' + info.id;
            window.localStorage.setItem('data_project', JSON.stringify(data.query.project));
        });

        if (token) {
            loadCommunity();
        } else {
            common.ajax('GET', '/order/index', {}, true, function () {
                loadCommunity();
            })
        }
        var pings = env.pings;
        pings();
    });

    $.init();
});
