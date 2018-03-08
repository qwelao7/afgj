require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-client-info", function (e, id, page) {
        var url = common.getRequest(),
            tpl = $('#tpl').html(),
            content = $('#content'),
            header = $('#header').html(),
            title = $('#title'),
            data = [];
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');

        // 跨域ajax请求

        function loadFamily() {
            var info = JSON.parse(window.localStorage.getItem('data_owner_client'));
            var html = juicer(header, info);
            console.log(info);

            title.append(html);
            $.ajax({
                url: env.ajax_data + "/pes/owner/" + url.id + "/tags?token=" + token,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        $.each(rsp.data, function (index, item) {
                            data[index] = item;
                        })
                        var data1 = {};
                        data1.data = data;
                        console.log(data1);
                        var html = juicer(tpl, data1);
                        content.append(html);
                        if (url.parentid) {
                            for (var i = 0; i < $('.tag_group_container').length; i++) {
                                if ($('.tag_group_container')[i].getAttribute('data-id') == url.parentid) {
                                    $('.tag_group_container')[i].setAttribute('data-toggle', '1');
                                }
                            }
                            for (var i = 0; i < $('.tag').length; i++) {
                                if ($('.tag')[i].getAttribute('data-parentid') == url.parentid) {
                                    $('.tag')[i].setAttribute('style', 'display:block');
                                }
                            }
                        }
                    }
                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            });
        };

        /**
         * 菜单编辑
         */
        $(document).on('click', '.tag_group', function () {
            var self = $(this);
            if (self.parent().data('toggle') == 0) {
                self.parent().children('.tag').css('display', 'block');
                self.parent().attr('data-toggle', 1);
                self.find('.icon-down').removeClass('icon-down').addClass('icon-up');
            } else if (self.parent().data('toggle') == 1) {
                self.parent().children('.tag').css('display', 'none');
                self.parent().attr('data-toggle', 0);
                self.find('.icon-up').removeClass('icon-up').addClass('icon-down');
            }
        });
        /**
         * 跳转标签列表页面
         */
        $(document).on('click', '.icon-ViewData-hll', function () {
            window.location.href = 'data-client-tag-list.html?id=' + url.id;
        });


        /**
         * 跳转编辑详情页
         */
        $(document).on('click', '.tag', function () {
            var self = $(this),
                id = url.id,
                tagid = self.data('tagid'),
                parentid = self.data('parentid');
            window.localStorage.setItem('tag_value', self.find('.tag-value').html());
            window.location.href = 'data-client-label-edit.html?id=' + id + '&tagid=' + tagid + '&parentid=' + parentid;
        });

        /**
         * 新增自定义标签
         */
        $(document).on('click', '.tag_group_extra', function () {
            var id = url.id;
            window.location.href = 'data-client-label-add.html?id=' + id;
        });


        //返回
        $(page).on('click', '#back', function () {
            window.location.href = 'data-client-search.html';
        });

        /** 搜索标签 **/
        $(document).on('focus', 'input[name=search]', function () {
            window.location.href = 'data-client-tag-search.html?id=' + url.id;
        });


        loadFamily();
        var pings = env.pings;
        pings();
    });

    $.init();
});
