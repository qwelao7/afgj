require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bbs-detail", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            list = $('#list').html(),
            tab = $('#tab').html(),
            title = $('#title').html(),
            container = $('#container'),
            nav = $('#nav'),
            ext = $('#ext'),
            bbs_id;

        //时间模板
        common.img();
        var time = function (data) {
            data = data.replace(/\-/g, '/');
            var date = new Date(data),
                M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-',
                D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ',
                h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':',
                m = date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes();
            return M + D + h + m;
        };
        juicer.register('time', time);
        var state = true,
            status = true;

        //加载数据
        function loadData(){
            common.ajax('GET','/forum/detail',{'id': url.id}, true,function(rsg){
                if(rsg.data.code == 0){
                    var data = rsg.data.info,
                        html = juicer(tpl,data),
                        htm = juicer(title,data.msg),
                        xhtml = juicer(tab,data),
                        thtml = juicer(list, data);
                    container.append(html);
                    container.append(thtml);
                    nav.append(xhtml);
                    $('#back').before(htm);
                    bbs_id = data.msg.bbs_id;
                    swiperPics(data.msg.attachment_content);
                }else{
                    $.alert('很抱歉,服务器失去连接,请稍后...');
                }
            })
        }

        //留言
        $(page).on('click','#comment',function(){
            $.prompt('请填写您的留言', function (value) {
                if (value == '') {
                    $.alert('很抱歉,留言不能为空!');
                    return;
                }
                value = $.trim(value);

                common.ajax('POST', '/forum/comment', {'id': url.id, 'content': value}, false, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;
                        var template = '<div class="user-item decoration-item lr-padding white">' +
                            '<div class = "user-item-img" ><img class= "head-img" src = "' + data.user.headimgurl + '" ></div>' +
                            '<div class="user-item-content">' +
                            '<h1 class = "item-two-line-title">' + data.user.nickname + '</h1>' +
                            '<h5 class = "item-two-line-detail" >' + time(data.comment.created_at)+ '</h5>' +
                            '</div>' +
                            '<br style = "clear: both">' +
                            '<h3 style = "padding-bottom: .4rem;margin-top:.4rem;margin-bottom: 0;padding-left: 14%">' + data.comment.content + '</h3>' +
                            '</div>';
                        $('#noComment').hide();
                        $('.tab-link').removeClass('active');
                        $('.tab-link').eq(0).addClass('active');
                        $('.tab').removeClass('active');
                        $('#tab2').addClass('active');
                        $('#tab2').prepend(template);
                    } else if (rsp.data.code == 110) {
                        $.alert('很抱歉,留言失败,请重试!');
                    }
                })
            });
        });

        //点赞
        $(document).on('click', '#toPraise', function (event) {
            if(!status) return;
            status = false;
            //点赞
            common.ajax('POST', '/forum/praise', {'id': url.id, 'type': 1}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        template = '<div class="user-item lr-padding white to-praise" data-id="' + data.praise.id + '">' +
                            '<div class="user-item-img"><img class="head-img" src="' + data.user.headimgurl + '"></div>' +
                            '<div class="user-item-content">' +
                            '<h2 class="item-two-line-title">' + data.user.nickname + '</h2><h5 class="item-two-line-detail">' + time(data.praise.created_at) + '</h5>' +
                            '</div> <br style="clear: both"> </div>';
                    $('#toPraise').replaceWith('<div class="button no-border font-dark" style="border-left: 1px solid #f6f6f9;height: 1rem;line-height: 1rem" id="praised"> <i class="iconfont icon-dianzanhll font-green" style="padding: 0;"></i>&nbsp;已赞</div>');
                    $('#noPraise').hide();
                    $('.tab-link').removeClass('active');
                    $('.tab-link').eq(1).addClass('active');
                    $('.tab').removeClass('active');
                    $('#tab3').addClass('active');
                    $('#tab3').prepend(template);
                    $('.tab-zan')[0].setAttribute('data-ispraise', true);
                    status = true;
                } else {
                    $.alert('很抱歉,点赞失败,请重试!', function() {
                        status = true;
                    });
                }
            });
        });
        $(document).on('click', '#praised', function(event) {
            if(!state) return;
            state = false;
            //取消点赞
            common.ajax('POST', '/forum/praise', {'id': url.id, 'type': 2}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        arr = [];
                    $('#praised').replaceWith('<div class="button no-border font-dark" style="border-left: 1px solid #f6f6f9;height: 1rem;line-height: 1rem" id="toPraise"> <i class="iconfont icon-zan1 font-dark" style="padding: 0;"></i>&nbsp;赞</div>');
                    $('.to-praise').each(function (index, item) {
                        arr.push($(item).data('id'));
                    });
                    var index = arr.indexOf(parseInt(data.id));
                    if (index != -1) {
                        $('.to-praise').eq(index).remove();
                    }
                    if (index != -1 && arr.length == 1) {
                        $('#noPraise').show();
                    }
                    $('.tab-link').removeClass('active');
                    $('.tab-link').eq(1).addClass('active');
                    $('.tab').removeClass('active');
                    $('#tab3').addClass('active');

                    $('.tab-zan')[0].setAttribute('data-ispraise', false);
                    state = true;
                } else {
                    $.alert('很抱歉,取消点赞失败,请重试!', function() {
                        state = true;
                    });
                }
            })
        });

        $('.click-more').live('click',function (e) {
            var self = $(this),
                publisherStatus = self.data('status');
            e.stopPropagation();
            var buttons1 = [
                {
                    text: '删帖',
                    onClick: function () {
                        $.modal({
                            title: '<div class="font-red">' +
                            '删除' +
                            '</div>',
                            text: '确认删除此贴？',
                            buttons: [
                                {
                                    text: '<span>取消</span>'
                                },
                                {
                                    text: '<span class="font-red">确定</span>',
                                    bold: true,
                                    onClick: function () {
                                        deletePost(self);
                                    }
                                },
                            ]
                        })
                    }
                },
                {
                    text: '禁言',
                    onClick: function () {
                        $.modal({
                            title: '<div class="font-green">' +
                            '禁言' +
                            '</div>',
                            text: '<div>对此用户禁言 <input type="number" style="display: inline-block;width: 1.5rem;background-color:#F8F8F8;border: none;border-bottom:1px solid #3d4145;text-indent: .2rem " id="silenceDate"/>天</div>',
                            buttons: [
                                {
                                    text: '取消'
                                },
                                {
                                    text: '确定',
                                    bold: true,
                                    onClick: function () {
                                        var val = $('#silenceDate').val().trim();
                                        if(val == '') {
                                            $.alert('请输入禁言天数!', '禁言失败');
                                            return;
                                        }

                                        silence(self,val);
                                    }
                                }
                            ]
                        })
                    }
                },
                {
                    text: '拉黑',
                    onClick: function () {
                        $.modal({
                            title: '<div class="font-green">' +
                            '拉黑' +
                            '</div>',
                            text: '确认拉黑此用户？',
                            buttons: [
                                {
                                    text: '取消'
                                },
                                {
                                    text: '确定',
                                    bold: true,
                                    onClick: function () {
                                        block(self);
                                    }
                                },
                            ]
                        })
                    }
                }
            ];
            var buttons2 = [
                {
                    text: '取消'
                }
            ];
            //用户是否加入社团,若退出只显示删帖选项
            if(publisherStatus) {
                if(self.data("cur") == 1 && self.data("self") == false){
                    var groups = [buttons1, buttons2];
                }else if(self.data("self") == true){
                    var groups = [buttons1.slice(0,1), buttons2];
                }else if(self.data("cur") == 2 && self.data("self") == false){
                    var groups = [buttons1.slice(0,2), buttons2];
                }
            }else {
                var groups = [buttons1.slice(0,1), buttons2];
            }
            $.actions(groups);
        });

        /**
         * 私聊
         */
        $(document).on('click', '#to_talk', function () {
            if ($(this).data('owner')) {
                $.alert('很抱歉,您不能和自己对话!');
                return;
            }else{
                window.location.href = "neighbor-chat.html?id=" + $(this).data("id");
            }

        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function() {

            window.location.href = 'bbs-list.html?id=' + bbs_id;
        });

        /**
         * 图片浏览
         */
        function swiperPics(data) {
            var arr = [];
            $.each(data, function(index, item) {
                arr.push(common.QiniuDamain + item);
            });

            $(document).on('click','.pb-popup',function () {
                var myPhotoBrowserPopup = $.photoBrowser({
                    photos : arr,
                    type: 'popup',
                    theme: 'dark'
                });
                myPhotoBrowserPopup.open();
                $('.close-popup').removeClass('icon').addClass('iconfont font-white');
                $('nav.bar-tab').remove();
            });
        }

        /**附件详情**/
        $(document).on('click','.to-detail', function() {
            var self = $(this),
                type = self.data('type'),
                id = self.data('id');

            if(type == 5) {
                window.location.href = 'bbs-event-detail.html?id=' + id+'&msId='+url.id;
            }else if(type == 4) {
                window.location.href = 'bbs-vote-detail.html?id=' + id+'&msId='+url.id;
            }
            
        });

        function deletePost(self) {
            if(!status) return;
            status = false;

            var parents = self.parents('.sm-margin'),
                m_id = parents.data('id');

            common.ajax('GET', '/forum/delete-post', {mId: m_id}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    window.location.href = 'bbs-list.html?id=' + bbs_id;
                }else {
                    $.alert('删帖失败,请重试!', '删帖失败');
                    status = true;
                }
            })
        }

        function silence(self,val) {
            if(!status) return;
            status = false;

            var m_id = self.parents('.sm-margin').data('id'),
                data = {};
            data.m_id = m_id;
            data.banned_time = val;

            if(val.length > 3){
                $.alert('禁言时间不能超过999天！');
                status = true;
            }else{
                common.ajax('POST', '/forum/silence', {data: data}, true, function(rsp) {
                    if(rsp.data.code == 0) {
                        $.alert('该用户已被禁言' + val + '天, 解禁时间是:' + rsp.data.info + '。', '禁言成功');
                        status = true;
                    }else if(rsp.data.code == 104) {
                        $.alert('该用户已被禁言', '禁言失败');
                        status = true;
                    }else {
                        $.alert('禁言失败,请重试!', '禁言失败');
                        status = true;
                    }
                });
            }
        }

        function block(self) {
            if(!status) return;
            status = false;

            var m_id = self.parents('.sm-margin').data('id');

            common.ajax('POST', '/forum/block', {m_id: m_id}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    $.alert('该用户已被拉黑!', '拉黑成功');
                    status = true;
                }else if(rsp.data.code == 104) {
                    $.alert('该用户已被拉黑!', '拉黑失败');
                    status = true;
                }else {
                    $.alert('拉黑失败,请重试!', '拉黑失败');
                    status = true;
                }
            })
        }

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
