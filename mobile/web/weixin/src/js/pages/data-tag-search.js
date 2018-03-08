require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-tag-search", function (e, id, page) {
        var url = common.getRequest(),
            tpl = $('#tpl').html(),
            content = $('#content'),
            data = {},
            list = [];
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');


        //搜索历史
        $(document).on('click', '#to-search', function () {
            var keywords = $('#search').val();
            list.length = 0;
            $.ajax({
                url: env.ajax_data + "/pes/owner/" + url.id + "/tags?token=" + token + "&keywords=" + keywords,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        if (rsp.data == '' || null || undefined) {
                            $.alert('很抱歉，无相关标签！')
                        } else {
                            console.log(rsp);
                            $.each(rsp.data, function (index, item) {
                                var n = item;
                                $.each(n, function (index, item) {
                                    var m = item;
                                    $.each(m, function (index, item) {
                                        list.push(item);
                                    })
                                })
                            })
                            data.list = list;
                            var html = juicer(tpl, data);
                            content.html('').append(html);
                        }

                    }
                },
                error: function () {
                    alert('fail');
                }
            });
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
            window.location.href = 'data-label-edit.html?id=' + id + '&tagid=' + tagid + '&parentid=' + parentid;
        });

        //返回
        $(document).on('click', '#back', function () {
            history.go(-1);
        });


        var pings = env.pings;
        pings();
    });

    $.init();
});
