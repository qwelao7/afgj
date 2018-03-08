require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-ranking", function (e, id, page) {
        var url = common.getRequest(),
            tpl = $('#tpl').html(),
            content=$('#content'),
            header = $('#header').html(),
            title = $('#title');
        var data = {},
            info = {},
            info1 = {};

        var token = '';
        token = url.token ? url.token : common.getCookie('openid');
        var project = JSON.parse(window.localStorage.getItem('data_project'));
        info.name = [];
        info.id = [];
        info.show = [];
        $.each(project, function (index, item) {
            info.name.push(item);
            info.id.push(index);
            info.show.push(item + '▾')
            if (index == url.id) {
                info1.name = item;
                info1.id = index;
            }
        })
        var html = juicer(header, info1);
        title.append(html);

        // 跨域ajax请求

        function loadCommunity() {
            $.ajax({
                url: env.ajax_data + "/pes/stat/rank?token=" + token + "&projectCode=" + url.id,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        data.rank=rsp.data;
                        $.each(data.rank,function (index,item) {
                            data.rank[index].index=index+1;
                            data.rank[index].src='http://pub.huilaila.net/avatar/defaultavatar.jpg';
                        })
                        console.log(data);
                        var html=juicer(tpl,data);
                        content.append(html);
                        picker();
                    } else {
                        $.alert('很抱歉！' + rsp.msg);
                    }

                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            });
        };



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
                        values: info.show,
                        displayValues: info.name
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
                    var id = info.id[info.show.indexOf(str)];
                    window.location.href = 'data-ranking.html?id=' + id;
                }
            });
        }


        /**
         * 点击遮罩层
         */
        $(document).on('click', '.modal-overlay', function () {
            $(this).removeClass('modal-overlay-visible');
            $(".picker").picker("close");
        });

        //返回
        $(page).on('click', '#back', function () {
            history.go(-1);
        });

        loadCommunity();
        var pings = env.pings;
        pings();
    });

    $.init();
});
