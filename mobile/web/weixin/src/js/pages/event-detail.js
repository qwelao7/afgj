require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    /**
     * url type 1- 详情 2- 报名 3-留言 4- 感谢
     */
    $(document).on("pageInit", "#event-detail", function (e, id, page) {
        var container = $('#container'),
            detail = $('#tab'),
            buttons = $('#buttons'),
            header = $('header'),
            more = $('#more').html(),
            comments = $('#comments').html(),
            joined = $('#joined').html(),
            thanks = $('#thanks').html(),
            tpl = $('#tpl').html(),
            actions = $('#actions').html(),
            deleteIcon = $('#deleteIcon').html();

        var hasRemark = false,
            isfree = true,
            isself = 3,
            hasJoin = false,
            loading = false,
            status = true,
            eventType,
            isSpecial = false,
            nextUrl = '',
            num = 2,
            nums,
            cur,
            tel;

        var url = common.getRequest();

        var actTitle = '';

        var format = function (data) {
            data = data.replace(/\-/g, '/');
            var day = new Date(data),
                dayMonth = day.getMonth() + 1,
                dayDate = day.getDate(),
                dayHour = day.getHours(),
                dayMinute = day.getMinutes();

            dayHour = (dayHour < 10) ? '0' + dayHour : dayHour;
            dayMinute = (dayMinute < 10) ? '0' + dayMinute : dayMinute;
            dayMonth = (dayMonth < 10) ? '0' + dayMonth : dayMonth;
            dayDate = (dayDate < 10) ? '0' + dayDate : dayDate;

            return dayMonth + '-' + dayDate + ' ' + dayHour + ':' + dayMinute;
        };
        var transText = function (data, param) {
            return data[param];
        };
        var getMoney = function (fee, point) {
            return parseFloat((fee * 100 + point) / 100).toFixed(2);
        };
        juicer.register('transText', transText);
        juicer.register('format', format);
        juicer.register('getMoney', getMoney);

        /** 切换报名/留言列表 **/
        $(document).on('click', '.tab_button', function () {
            var self = $(this),
                key = self.index();

            var path = 'event-detail.html?id=' + url.id + '&type=' + parseInt(key + 1);

            if (url.dir) {
                window.location.href = path + '&dir=' + url.dir;
            } else {
                window.location.href = path + '&refer=' + url.refer;
            }
        });

        /**
         * 点击tag标签
         **/
        $(document).on('click', '#tag', function () {
            $('#popup').css('display', 'block');
            $('#modal').toggleClass('modal-overlay-visible');
        });
        $(document).on('click', '#modal', function () {
            $('#popup').css('display', 'none');
            $('#modal').toggleClass('modal-overlay-visible');
            $('.actions-modal').addClass('modal-out');
            setTimeout(function () {
                $('.actions-modal').remove();
            }, 200);
        });

        /**
         * 返回列表页
         * **/
        $(document).on('click', '#back', function () {
            returnBack();
        });

        /**
         * 报名已满
         */
        $(document).on('click', '#join_enough', function () {
            $.alert('很抱歉,本次活动报名人数已满!', '报名失败');
        });

        // 退款按钮
        $(document).on('click', '.refund-btn', function () {
            var _this = $(this),
                uid = _this.data('uid');

            common.ajax('GET', '/events/event-pay-money', {
                'event_id': url.id,
                'uid': uid
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    $.modal({
                        title: '<span class="h2 font-green">' + data.nickname + '已交' + data.pay + '元</span>',
                        text: '<div>' +
                        '<p class="vehicle-modal-text"><span>退费(元)</span><input type="number" name="refund"/></p>' +
                        '</div>',
                        buttons: [
                            {
                                text: '取消',
                                onClick: function () {
                                    $('#modal').removeClass('modal-overlay-visible');
                                }
                            },
                            {
                                text: '确定',
                                bold: true,
                                onClick: function () {
                                    var val = $.trim($('input[name=refund]').val());

                                    if (val == '') {
                                        $.alert('请填写退款金额!', '提交失败');
                                        return false
                                    }

                                    toRefund(val, uid, _this);
                                }
                            }
                        ]
                    })
                }
            });
        });

        /**
         * 删除活动
         * **/
        $(document).on('click', '#delete', function () {
            $.modal({
                text: '您确定删除该活动？',
                buttons: [
                    {
                        text: '取消',
                        onClick: function () {
                            $('#popup').css('display', 'none');
                        }
                    },
                    {
                        text: '确定',
                        bold: true,
                        onClick: function () {
                            $('#popup').css('display', 'none');
                            common.ajax('POST', '/events/cancel', {'id': url.id}, true, function (rsp) {
                                if (rsp.data.code == 0) {
                                    $.alert('删除活动成功!', '删除成功', function () {
                                        returnBack();
                                    })
                                } else {
                                    $.alert('很抱歉,' + rsp.data.message, '删除失败');
                                }
                            })
                        }
                    }
                ]
            })
        });

        /**
         * 留言评论
         * **/
        $(document).on('click', '#to-comment', function () {
            $.prompt('请填写您的留言', function (value) {
                if (value == '') {
                    $.alert('很抱歉,留言不能为空!');
                    return;
                }
                value = value.trim();
                common.ajax('POST', '/events/comment', {'event_id': url.id, 'content': value}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('留言成功!', function () {
                            window.location.href = 'event-detail.html?id=' + url.id + '&type=3' + '&refer=' + url.refer;
                        });
                    } else {
                        $.alert('很抱歉,留言失败,请重试!', '留言失败');
                    }
                })
            });
        });

        /**
         * 去感谢
         */
        $('#to-thank').live('click', function () {
            if (isself == 1) {
                $.alert('很抱歉,您不能给自己赠送积分!', '温馨提示');
            } else {
                location.href = 'event-3q.html?id=' + url.id;
            }
        });

        /**
         * 咨询
         **/
        $(document).on('click', '#to-talk', function () {
            if (isself == 1) {
                $.alert('很抱歉,您不能和自己对话!', '咨询失败');
            } else {
                location.href = 'tel://' + tel;
            }
        });

        /**
         * 前往报名
         **/
        $(document).on('click', '#to-join', function () {
            if (!status) return;
            status = false;

            if (hasJoin) {
                $.alert('很抱歉,你已参加了本活动!', '报名失败');
            } else {
                // 报名操作
                applyAction();
            }
        });

        /**
         * 取消报名
         **/
        $(document).on('click', '#cancel-join', function () {
            if (!status) return false;
            status = false;

            if (isfree) {
                commonCancel();
            } else {
                getUserApplyInfo();
            }
        });

        /**
         * 前往编辑
         */
        $('#edit').on('click', function () {
            location.href = 'event-edit.html?id=' + url.id;
        });

        /** 签到码 **/
        $('.qr_code').live('click', function () {
            if (isself == 3) {
                location.href = 'event-qrcode.html?id=' + url.id;
            } else {
                location.href = 'event-signin.html?events_id=' + url.id;
            }
        });

        /** 无限滚动 **/
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

            if (url.type == 3) {
                loadComments(num);
            } else if (url.type == 2) {
                loadJoined(num);
            }

            $.refreshScroller();
        });

        $(document).on('keyup', 'input[name=refund]', function () {
            var _this = $(this),
                reg = _this.val().match(/\d+\.?\d{0,2}/),
                txt = '';
            if (reg != null) {
                txt = reg[0];
            }
            _this.val(txt);
        });

        $(document).on('click', '.modal-overlay', function () {
            $(this).removeClass('modal-overlay-visible');
            $('.modal-in').remove();
        });

        $(document).on('click', '.event_worker', function () {
            location.href = 'event-employee.html?id=' + url.id;
        });

        $(document).on('click', '.auth_success', function () {
            var self = $(this),
                parent = self.parents('.buttons-row'),
                root = self.parents('.to-praise'),
                showDesc = root.find('.check_status_desc'),
                apply_id = parent.data('apply'),
                user_id = parent.data('user');

            $.confirm('确定审核通过么?', function () {
                common.ajax('GET', '/events/apply-check-result', {
                    'id': apply_id,
                    'user_id': user_id,
                    'events_id': url.id,
                    'check_status': 1
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('该报名审核通过!', '审核通过', function () {
                            parent.remove();
                            showDesc.remove();
                        })
                    } else {
                        $.alert('很抱歉,审核失败!失败原因:' + rsp.data.message, '审核失败')
                    }
                })
            });
        });

        $(document).on('click', '.auth_fail', function () {
            var self = $(this),
                parent = self.parents('.buttons-row'),
                root = self.parents('.to-praise'),
                apply_id = parent.data('apply'),
                user_id = parent.data('user');

            $.prompt('审核不通过理由:', function (value) {
                //submit

                common.ajax('GET', '/events/apply-check-result', {
                    'id': apply_id,
                    'user_id': user_id,
                    'events_id': url.id,
                    'check_status': 2,
                    'fail_reason': value
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('该报名未通过审核!', '提交成功', function () {
                            parent.remove();

                            //报名人数减一
                            var num = $('#joined-btn').find('span').text();
                            $('#joined-btn').find('span').text(parseInt(num) - 1);
                        })
                    } else {
                        $.alert('很抱歉,提交失败!失败原因:' + rsp.data.message, '提交失败');
                    }
                })
            });
        });

        $(document).on('click', '.con_success', function () {
            var self = $(this),
                parent = self.parents('.buttons-row'),
                refund_id = parent.data('refund_id');

            allowRefund(refund_id, parent);
        });

        $(document).on('click', '.con_fail', function () {
            var self = $(this),
                parent = self.parents('.buttons-row'),
                refund_id = parent.data('refund_id');

            rejectRefund(refund_id, parent);
        });

        function toRefund(money, uid, element) {
            common.ajax('GET', '/events/pay-back-apply', {
                'event_id': url.id,
                'uid': uid,
                'money_paid': money
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('退款成功,请耐心等待1-2天,等待后台审核!', '退款成功', function () {
                        element.remove();
                    })
                } else {
                    $.alert('很抱歉,退款失败!失败原因:' + rsp.data.message, '退款失败');
                }
            })

        }

        function loadData() {
            common.ajax('GET', '/events/detail', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data),
                        htm = juicer(actions, data),
                        hm = juicer(deleteIcon, data);

                    container.append(html);
                    buttons.append(htm);
                    header.append(hm);

                    /** 参数初始化 **/
                    initParams(data);

                    // 动态设置头部
                    common.setDocumentTitle(data.title);

                    initLoad();
                    init();

                    getConfig();
                } else {
                    var template = "<h3 class='grey' style='text-align: center;margin-top: 4rem;'>很抱歉,暂无活动!</h3>";
                    container.append(template);
                }
            });
        }

        function initParams(data) {
            cur = data.creater;
            isself = data.isself;
            isfree = (data.free == 0) ? false : true;
            hasJoin = data.is_join;
            eventType = data.events_type;
            hasRemark = (data.ext_fields && !common.isEmptyObject(data.ext_fields)) ? true : false;
            tel = data.tel;
            actTitle = data.title;
            isSpecial = (data.is_special == 1) ? true : false;
            nextUrl = data.next_url;
        }

        function init() {
            $('.tab-link').removeClass('active');
            if (!url.type) {
                $('.tab-link').eq(0).addClass('active');
            } else {
                $('.tab-link').eq(parseInt(url.type) - 1).addClass('active');
            }
        }

        function initLoad() {
            if (!url.type || url.type == 1) {
                loadDesc();
            } else if (url.type == 2) {
                loadJoined(1);
            } else if (url.type == 3) {
                loadComments(1);
            } else if (url.type == 4) {
                loadThanks();
            }
        }

        function loadDesc() {
            common.ajax('GET', '/events/desc', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        htm = juicer(more, data);
                    detail.append(htm);
                    $('.infinite-scroll-preloader').hide();
                } else {
                    var template = "<h3 class='grey' style='text-align: center;margin-top: 4rem;'>很抱歉,暂无详情!</h3>";
                    detail.append(template);
                }
            })
        }

        function loadComments(pages) {
            common.ajax('GET', '/events/comment-list', {
                'id': url.id,
                'page': pages
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    loading = false;

                    if (pages == 1) {
                        if (data.list.length < 1) {
                            renderError('暂无留言记录!')
                        }

                        nums = data.pagination.pageCount;

                        if (nums == 1) {
                            removeInfinite();
                        }
                    }

                    var html = juicer(comments, data);
                    detail.append(html);

                    if (pages > 1) {
                        num++;
                    }
                } else {
                    if (pages == 1) {
                        renderError('暂无留言记录!');
                    }
                }
            })
        }

        function loadJoined(pages) {
            common.ajax('GET', '/events/apply-list', {
                'id': url.id,
                'page': pages
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    loading = false;

                    if (pages == 1) {
                        if (data.list.length < 1) {
                            renderError('暂无人员加入!');
                        } else {
                            nums = data.pagination.pageCount;

                            if (nums == 1) {
                                removeInfinite();
                            }
                        }
                    }

                    data['isself'] = isself;
                    data['isfree'] = isfree;
                    var html = juicer(joined, data);
                    detail.append(html);

                    if (pages > 1) {
                        num++;
                    }
                } else {
                    if (pages == 1) {
                        renderError('暂无人员加入!')
                    }
                }
            })
        }

        function loadThanks() {
            common.ajax('GET', '/events/events-thanks-list', {
                'id': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    loading = false;

                    if (data.list.length < 1) {
                        renderError('暂无感谢记录!');
                    } else {
                        var html = juicer(thanks, data);

                        detail.append(html);

                        removeInfinite();
                    }
                } else {
                    renderError('暂无感谢记录!')
                }
            })
        }

        function removeInfinite() {
            // 加载完毕，则注销无限加载事件，以防不必要的加载
            $.detachInfiniteScroll($('.infinite-scroll'));
            // 删除加载提示符
            $('.infinite-scroll-preloader').remove();
            return false;
        }

        function renderError(str) {
            var tem = "<h3 class='grey' style='text-align: center;margin-top: 4rem;'>" + str + "</h3>";
            detail.append(tem);
            $('.infinite-scroll-preloader').remove();
            return false;
        }

        /** 报名操作 **/
        function applyAction() {
            if (isSpecial && nextUrl != '' && nextUrl != undefined) {
                window.location.href = nextUrl;
            } else {
                if (eventType == 'general' && !hasRemark && isfree) {
                    //提交
                    common.ajax('POST', '/events/apply', {
                        'id': url.id
                    }, true, function (rsp) {
                        if (rsp.data.code == 0) {
                            $.alert('报名成功!', '报名成功', function () {
                                window.location.reload();
                            })
                        } else {
                            $.alert('很抱歉,' + rsp.data.message, function () {
                                status = true;
                            })
                        }
                    })

                } else {
                    var path = (url.refer) ? '&refer=' + url.refer : '';
                    window.location.href = 'event-signup.html?id=' + url.id + path;
                }
            }
        }

        function commonCancel () {
            $.modal({
                text: '您确定取消报名吗?',
                buttons: [
                    {
                        text: '取消',
                        onClick: function () {
                            status = true;
                        }
                    },
                    {
                        text: '确定',
                        bold: true,
                        onClick: function () {
                            var params = {
                                'events_id': url.id,
                                'pay': 0
                            };

                            toCancelApply(params);
                        }
                    }
                ]
            })
        }

        function toCancelApply (params) {
            common.ajax('GET', '/events/apply-cancel', params, true, function (rsp) {
                if (rsp.data.code == 0) {
                    if (parseFloat(params['pay']).toFixed(2) == 0.00 || params['pay'] == '') {
                        $.alert('取消报名成功!', '取消成功', function () {
                            window.location.reload();
                        });
                    } else {
                        $.alert('退费申请已提交,请耐心等待!', '提交成功', function() {
                            window.location.reload();
                        });
                    }
                } else {
                    if (parseFloat(params['pay']).toFixed(2) == 0.00 || params['pay'] == '') {
                        $.alert('很抱歉,取消报名失败!失败原因:' + rsp.data.message, '取消失败', function () {
                            status = true;
                        });
                    } else {
                        $.alert('退费申请提交失败!' + rsp.data.message, '提交失败', function () {
                            status = true;
                        });
                    }
                }
            });
        }

        function forRefund (data) {
            var str = '<p class="vehicle-modal-text">总费用: ' + data.total_fee + '元</p>' +
                '<p class="vehicle-modal-text">友元抵扣: ' + parseFloat(data.youyuan_fee/100) + '元</p>' +
                '<p class="vehicle-modal-text">实付: ' + data.cash_fee + '元</p>' +
                '<input type="text" name="refund_money" value="' + data.total_fee + '" style="border: 1px solid #ddd;font-size: .7rem;font-weight: normal;width: 90%;text-indent: .2rem;border-radius: .2rem;line-height: 1.5rem;" placeholder="请填写退款费用...">';
            
            $.modal({
                title: '活动退费申请',
                text: str,
                buttons: [
                    {
                        text: '取消',
                        onClick: function () {
                            $('#modal').removeClass('modal-overlay-visible');
                            status = true;
                        }
                    },
                    {
                        text: '确定',
                        bold: true,
                        onClick: function () {
                            var money = $.trim($('input[name=refund_money]').val());
                            money = parseFloat(money).toFixed(2);

                            var params = {
                                'events_id': url.id,
                                'pay': money
                            };

                            toCancelApply(params);
                        }
                    }
                ]
            })
        }
        
        function getUserApplyInfo () {
            common.ajax('GET', '/events/get-user-apply-info', {
                'event_id': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    
                    forRefund(data);
                    inputListening();
                } else {
                    $.alert('很抱歉,数据获取失败,请重试!', '数据错误', function() {
                        status = true;
                    });
                }
            })
        }

        function inputListening () {
            $(document).on('keyup', 'input[name=refund_money]', function() {
                var _this = $(this),
                    reg = _this.val().match(/\d+\.?\d{0,2}/),
                    txt = '';
                if (reg != null)
                {
                    txt = reg[0];
                }
                _this.val(txt);
            });
        }

        function rejectRefund(refund_id, parent) {
            $.prompt('审核不通过理由:', function (value) {
                //submit

                common.ajax('POST', '/events/apply-cancel-operate', {
                    'id': refund_id,
                    'status': 3,
                    'reason': value
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('退费申请被拒绝!', '提交成功', function() {
                            // 回调
                            var tem = '<div class="list-title white"><h3>退费拒绝理由：<span class="font-green">' + value +'</span></h3></div>';

                            parent.before(tem);
                            parent.remove();
                        })
                    } else {
                        $.alert('很抱歉,提交失败!失败原因:' + rsp.data.message, '提交失败')
                    }
                });
            });
        }

        function allowRefund(refund_id, parent) {
            $.confirm('确定审核通过么?', function () {
                common.ajax('POST', '/events/apply-cancel-operate', {
                    'id': refund_id,
                    'status': 2
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('该报名审核通过!', '审核通过', function () {
                            // 回调
                            parent.remove();

                            var data = rsp.data.info;
                            var num = $('#joined-btn').find('span').text();
                            $('#joined-btn').find('span').text(parseInt(num) - parseInt(data));
                        })
                    } else {
                        $.alert('很抱歉,提交失败!失败原因:' + rsp.data.message, '提交失败')
                    }
                })
            });
        }

        /**
         * 返回列表
         */
        function returnBack() {
            if (url.dir) {
                location.href = 'event-' + url.dir + '.html';
                return;
            }

            var path = (url.refer) ? ('?id=' + url.refer) : '';
            location.href = 'event-list.html' + path;
        }

        /**  获取微信配置 **/
        function getConfig() {
            common.ajax('POST', '/wechat/config', {href: window.location.href}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data = JSON.parse(data);
                    wx.config({
                        debug: false,
                        appId: data.appId,
                        timestamp: data.timestamp,
                        nonceStr: data.nonceStr,
                        signature: data.signature,
                        jsApiList: [
                            'checkJsApi',
                            'onMenuShareTimeline',
                            'onMenuShareAppMessage',
                        ]
                    });
                    wx.ready(function () {
                        var title = actTitle;
                        var desc = '回来啦社区';
                        var imgUrl = common.QiniuDamain + 'logo.jpg';
                        var link = location.href;
                        wx.onMenuShareAppMessage({
                            title: title, // 分享标题
                            desc: desc, // 分享描述
                            link: link, // 分享链接
                            imgUrl: imgUrl, // 分享图标
                            type: '', // 分享类型,music、video或link，不填默认为link
                            dataUrl: '' // 如果type是music或video，则要提供数据链接，默认为空
                        });
                        wx.onMenuShareTimeline({
                            title: title, // 分享标题
                            link: link, // 分享链接
                            imgUrl: imgUrl // 分享图标
                        });
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});
