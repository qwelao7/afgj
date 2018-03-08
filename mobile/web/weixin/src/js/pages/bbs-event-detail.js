require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bbs-event-detail", function (e, id, page) {
        var url = common.getRequest();
        common.img();

        var tpl = $('#tpl').html(),
            detail = $('#detail').html(),
            list = $('#list').html(),
            nav = $('#nav'),
            content = $('#content');

        var status = true,
            len;

        function loadData() {
            common.ajax('GET', '/forum/act-detail', {id: url.id}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(detail, data.val),
                        htm = juicer(tpl, data.val);

                    content.append(html);
                    nav.append(htm);
                    if(data.list.length > 0){
                        var xhtml = juicer(list,data);
                        content.append(xhtml);
                    }

                    len = data.list.length;
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无活动详情!</h3>";
                    content.append(template);
                }
            })
        }

        $(document).on('click', '.to-enroll', function() {
            if(!status) return;
            status = false;

            var self = $(this),
                join = self.data('join');

            if(join) {
                common.ajax('GET', '/forum/act-enroll', {id: url.id, type: 2}, true, function(rsp) {
                    if(rsp.data.code == 0) {
                        var arr = [],
                            index,
                            template = '<button class="bar bar-tab to-enroll" style="text-align: center;padding: .6rem;color: #fff;" data-join="false"> 我要报名 </button>';
                        self.replaceWith(template);
                        $('.member').forEach(function(item){
                            arr.push($(item).data('id'));
                        });
                        index = arr.indexOf(parseInt(rsp.data.info));
                        $('.member').eq(index).remove();
                        if($('.member').length == 0) {
                            $('.list-title').remove();
                        }

                        status = true;
                    }else {
                        $.alert('很抱歉,取消报名失败,请重试!', function() {
                            status = true;
                        })
                    }
                })
            }else {
                common.ajax('GET', '/forum/act-enroll', {id: url.id, type: 1}, true, function(rsp) {
                    if(rsp.data.code == 0) {
                        var data = rsp.data.info,
                            template1 = '<button class="bar bar-tab to-enroll" style="text-align: center;padding: .6rem;color: #fff;background-color: #d7d7d7" data-join="true"> 取消报名 </button>',
                            template2 = '<div class="user-item lr-padding white community-loupan last-noborder member" data-id="'+data.account_id
                                +'"><div class="user-item-img"><img src="'+data.headimgurl+
                                '" style="border-radius: 50%"></div><div class="user-item-content"><h2>'+data.nickname;
                        if(data.user_role == 1){
                            var template3 = '<img src="weixin/src/css/img/crown-gold.png" style="width: 1rem"></h2></div></div>';
                        }else if(data.user_role == 2){
                            var template3 = '<img src="weixin/src/css/img/crown.png" style="width: 1rem"></h2></div></div>';
                        }else{
                            var template3 = '</h2></div></div>';
                        }
                        template2 = template2 + template3;
                        self.replaceWith(template1);
                        if(len > 0) {
                            $('.list-title').after(template2);
                        }else {
                            content.append('<div class="list-title white has-border-bottom lr-padding" style="line-height: 1.7rem">已报名用户 </div>');
                            content.append(template2);
                        }
                        status = true;
                    }else if(rsp.data.code == 104){
                        $.alert('很抱歉,本活动报名人数已满,感谢您对活动的支持!', function() {
                            status = true;
                        });
                    }else {
                        $.alert('很抱歉,报名失败,请重试!', '取消失败', function() {
                            status = true;
                        })
                    }
                })
            }
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function() {

            window.location.href = 'bbs-detail.html?id=' + url.msId;
        });

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
