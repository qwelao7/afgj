require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';

    $(document).on('pageInit', '#neighbor-list', function(e, id, page) {
        var tpl = $('#neighbour').html(),
            container = $('#container');

        //时间戳
        var timestamp = new Date().getTime();

        //模板自定义函数
        var bbsImg = function(data) {
            var qiniu = common.QiniuDamain;
            if(data == null || data == '') {
                data = env.defaultCommunityImg;
            }
            return qiniu + data;
        };
        var toString = function(data) {
            return JSON.stringify(data);
        };
        juicer.register('toString', toString);
        juicer.register('bbsImg', bbsImg);

        function loadData() {
            common.ajax('GET', '/neighbour/index', {}, true, function(rsp) {
                var data = rsp.data.info,
                    neighbours = data.neighbours,
                    total_num = 0;
                $.each(neighbours, function (index, item) {
                    total_num += item.length;
                });
                data.total_num = total_num;
                var template = juicer(tpl, data);
                container.append(template);
            })
        }
        loadData();

        /** 前往楼盘通讯里页面 **/
        $(page).on('click', '.community-loupan', function() {
            var loupanId = $(this).data('loupan'),
                name = $(this).data('name');
            common.saveStorage(name);
            window.location.href = 'address-list.html?id=' + loupanId + '&time=' + timestamp;
        })

        /** 前往关注好友详情页 **/
        $(page).on('click', '.follow-neighbor', function() {
            var userId = $(this).data('user'),
                person = $(this).data('person');
            common.saveStorage(person);
            window.location.href = 'neighbor-detail.html?id=' + userId + '&time=' + timestamp;
        })

        /** 上拉刷新 **/
        $('.pull-to-refresh-content').on('refresh', function() {
            window.location.reload();
            // 加载完毕需要重置
            document.ready(function() {
                $.pullToRefreshDone('.pull-to-refresh-content');
            })
        })
        
        //返回
        $('#back').on('click', function() {
            location.href = common.ectouchPic;
        });

        var pings = env.pings;pings();
    });

    $.init();
})