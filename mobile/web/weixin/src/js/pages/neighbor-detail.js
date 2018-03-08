require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#neighbor-detail', function (e, id, page) {
        var tpl = $('#tpl').html(),
            tags = $('#tags').html(),
            container = $('#container'),
            header = $('#header');

        //获取url参数
        var url = common.getRequest(),
            userId = url.id,
            pass;

        //模板自定义函数
        common.img();

        common.ajax('GET', '/neighbour/view', {userId: url.id}, false, function (rsp) {
            var data = rsp.data.info,
                template = juicer(tpl, data),
                html = juicer(tags, data);

            pass = data.commonCommunitys[0];
            container.append(template);
            header.append(html);
        });

        /** 切换tab **/
        $(page).on('click', '.tab-item', function () {
            console.log($(this));
        });

        /** 修改输入备注名 **/
        $(page).on('click', '.nick-name', function () {
            $.prompt('请输入备注名', function (value) {
                if (value == "") {
                    $.alert('请输入备注名');
                    return;
                }
                //添加或修改备注名
                common.ajax('POST', '/neighbour/remarks', {userId: url.id, content: value}, true, function (rsp) {
                    if (rsp.data.code == 0) location.reload();
                    else if(rsp.data.code == 102) {
                        $.alert('请先关注该用户');
                    }else {
                        $.alert('很抱歉,备注名设置失败,请重试!')
                    };
                })
            });
        });

        /** 取消关注 **/
        $(page).on('click', '#unfollow', function () {
            common.ajax('GET', '/user/unfollow', {userId: url.id}, true, function (rsp) {
                //显示关注按钮
                $('#sendMsg').after('<button class="button btn-lg btn-border" id="follow">关注</button>');
                //去除取消关注
                $('#unfollow').hide();
                $('#tips').toggleClass('unshow');
                $('#tag').hide();
            })
        })

        /** 关注用户 **/
        $(page).on('click', '#follow', function () {
            common.ajax('GET','/user/follow', {userId: url.id}, true, function(rsp) {
                //不显示关注按钮
                $('#follow').remove();
                $('#unfollow').show();

                if($('#tag').length != 0){
                    $('#tag').show();
                }else {
                    var template = '<div class="detail-drop-down unshow" id="tips"><h4 id="unfollow"><a class="iconfont icon-cancel icon-grey" style="font-size: .5rem;margin-right: .5rem;margin-left: .2rem"></a>取消关注</h4></div>',
                        tem = '<a class="iconfont icon-edit pull-right icon-white open-panel" id="tag"></a>';
                    container.prepend(template);
                    header.append(tem);
                }
            })
        })

        /** 发送消息 跳转至单聊页面**/
        $(page).on('click', '#sendMsg', function () {
            window.location.href = "neighbor-chat.html?id=" + url.id;
        })

        /** 切换tag **/
        $(page).on('click', '#tag', function () {
            $('#tips').toggleClass('unshow');
        })

        /**跳转投诉界面 **/
        $(page).on('click', '#complaint', function () {
            localStorage.setItem('pass', pass);
            window.location.href = "new-complaint.html?id=" + url.id;
        })

        var pings = env.pings;pings();

    })

    $.init();
})