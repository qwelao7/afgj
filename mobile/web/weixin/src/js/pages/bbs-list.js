require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bbs-list", function (e, id, page) {
        var url = common.getRequest();
        common.img();

        var container = $('#container'),
            header = $('#header'),
            title = $('#title').html(),
            tpl = $('#tpl').html(),
            loading = false,
            status = true,
            num = 2,
            nums;

        function loadData() {
            common.ajax('GET', '/forum/list', {bbsId: url.id, page: 1}, true, function (rsp) {
                var data = rsp.data.info,
                    htm = juicer(title, data.title);
                header.prepend(htm);
                if (rsp.data.code == 0) {
                    nums = data.pagination.pageCount;

                    var html = juicer(tpl, data);
                    container.append(html);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无新鲜事!</h3>";
                    container.append(template);
                    $('.infinite-scroll-preloader').remove();
                }
            })
        }

        function deletePost(self) {
            if(!status) return;
            status = false;

            var parents = self.parents('.lg-margin'),
                m_id = parents.data('id');

            common.ajax('GET', '/forum/delete-post', {mId: m_id}, true, function(rsp) {
              if(rsp.data.code == 0) {
                  parents.remove();
                  status = true;
              }else {
                  $.alert('删帖失败,请重试!', '删帖失败');
                  status = true;
              }
            })
        }

        function silence(self,val) {
            if(!status) return;
            status = false;

            var m_id = self.parents('.lg-margin').data('id'),
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
                    }else if(rsp.data.code == 105){
                        $.alert('该用户已被拉黑!', '禁言失败');
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

            var m_id = self.parents('.lg-margin').data('id');
            
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

        $(document).on('infinite', '.infinite-scroll', function () {
            if (loading) return;
            loading = true;

            if (num > nums) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }

            common.ajax('GET', '/forum/list', {bbsId: url.id, page: num}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    loading = false;
                    var data = rsp.data.info;

                    var html = juicer(tpl, data);
                    container.append(html);

                    num++;
                }
            });

            $.refreshScroller();
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
                            text: '<div>对此用户禁言 <input type="number" oninput="if(value.length>3)value=value.slice(0,3)" style="-webkit-appearance:none;outline:none;appearance:none;display: inline-block;width: 2.5rem;background-color:#F8F8F8;border: none;border-bottom:1px solid #3d4145;border-radius:0;text-indent: .2rem " id="silenceDate"/>天</div>',
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
                var groups = [buttons1.slice(0,1),buttons2];
            }

            $.actions(groups);
        });

        $(document).on('click', '.user-item', function() {
            var self = $(this),
                id = self.parents('.lg-margin').data('id');
            window.location.href = 'bbs-detail.html?id=' + id;
        });

        $(document).on('click', '.to-praise', function () {
            var self = $(this),
                parent = self.parent(),
                isPraise = parent.data('praise'),
                m_id = self.parents('.lg-margin').data('id');

            if (!status) return;
            status = false;

            if (isPraise) {
                common.ajax('POST', '/forum/praise', {id: m_id, type: 2}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;
                        self.html('<i class="iconfont icon-zan1" style="padding: 0;"></i><span class="font-grey">&nbsp;' + data.num + '</span>');
                        parent[0].setAttribute('data-praise', false);
                    } else {
                        $.alert('很抱歉,取消点赞失败,请重试!', '取消点赞失败');
                    }
                    status = true;
                })
            } else {
                common.ajax('POST', '/forum/praise', {id: m_id, type: 1}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;
                        self.html('<i class="iconfont icon-dianzanhll font-green" style="padding: 0;"></i><span class="font-green">&nbsp;' + data.num + '</span>');
                        parent[0].setAttribute('data-praise', true);
                    } else {
                        $.alert('很抱歉,点赞失败,请重试!', '点赞失败');
                    }
                    status = true;
                })
            }
        });

        $(document).on('click', '#circle', function () {
            window.location.href = 'circle-detail.html?id=' + url.id;
        });

        $(document).on('click', '.to-detail,.content-wrap', function () {
            var self = $(this),
                m_id = self.parents('.lg-margin').data('id');
            window.location.href = 'bbs-detail.html?id=' + m_id;
        });
        
        $(document).on('click','.to-chat', function() {
            var self = $(this),
                uid = self.parents('.lg-margin').data('uid'),
                isSelf = self.parents('.lg-margin').data('self');

            if(isSelf) {
                $.alert('很抱歉,您不能和自己对话!');
            }else {
                window.location.href = "neighbor-chat.html?id=" + uid;
            }
        });

        $(document).on('click', '#create', function() {
           if(!status) return;
            status = false;

            common.ajax('GET', '/forum/status', {bbsId: url.id}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    status = true;
                    window.location.href = 'bbs-create.html?id=' + url.id;
                }else if(rsp.data.code == 103) {
                    $.alert('很抱歉,您已被管理员禁言,解禁时间还剩:' + rsp.data.info +'天','温馨提示');
                    status = true;
                }else if(rsp.data.code == 102) {
                    $.alert('很抱歉,您的身份在审核中,请耐心等待...!', '温馨提示');
                    status = true;
                }else if(rsp.data.code == 104) {
                    $.alert('很抱歉,您已被拉黑!', '温馨提示');
                    status = true;
                }else if(rsp.data.code == 101) {
                    $.modal({
                        title: '温馨提示',
                        text: '很抱歉,您还未加入本社团,无法创建新鲜事!',
                        buttons: [
                            {
                                text: '知道了',
                                onClick: function() {
                                    status = true;
                                }
                            },
                            {
                                text: '加入社团',
                                bold: true,
                                onClick: function () {
                                    status = true;
                                    window.location.href = 'circle-detail.html?id=' + url.id;
                                }
                            }
                        ]
                    });
                }
            })
        });

        loadData();

        var pings = env.pings;pings();

    });

    $.init();
});

