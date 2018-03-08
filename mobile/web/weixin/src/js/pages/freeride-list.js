require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#freeride-list", function (e, id, page) {
        //参数
        var url = common.getRequest();

        var container = $('#container'),
            title = $('.title'),
            tpl = $('#tpl').html(),
            top = $('#top').html(),
            view = $('#view').html(),
            fangs = $('#fangs').html();

        var pageSize = 4,
            pages,  //列表页数
            backs, //回顾页数
            page = 2,
            back = 2;

        var format = function (data) {
            data = data.replace(/\-/g, '/');
            var cur = new Date(),
                curTime = cur.getFullYear() + '/' + (cur.getMonth() + 1) + '/' + cur.getDate() + ' ' + '00:00:00',
                today = new Date(curTime).getTime();
            var day = new Date(data),
                dayTime = day.getTime(),
                dayYear = day.getFullYear(),
                dayMonth = day.getMonth() + 1,
                dayDate = day.getDate(),
                dayHour = day.getHours(),
                dayMinute = day.getMinutes();

            dayHour = (dayHour < 10) ? '0' + dayHour : dayHour;
            dayMinute = (dayMinute < 10) ? '0' + dayMinute : dayMinute;
            dayMonth = (dayMonth < 10) ? '0' + dayMonth : dayMonth;
            dayDate = (dayDate < 10) ? '0' + dayDate : dayDate;

            var stramp = dayTime - today;

            if (stramp > 0 && stramp <= 86400000) {
                data = '今天' + dayHour + ':' + dayMinute;
            } else if (stramp > 86400000 && stramp <= 172800000) {
                data = '明天' + dayHour + ':' + dayMinute;
            } else if (stramp > 172800000 && stramp <= 259200000) {
                data = '后天' + dayHour + ':' + dayMinute;
            } else {
                data = dayYear + '-' + dayMonth + '-' + dayDate + ' ' + dayHour + ':' + dayMinute;
            }
            return data;
        };
        juicer.register('format', format);

        var fang = [],
            ids = [];

        //无限滚动
        var page,
            back,
            loading = false,
            loaded = false;

        /**
         * 获取当前用户的楼盘
         */
        function loadFang() {
            common.ajax('GET', '/ride-sharing/account-community', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    fang = data.name;
                    ids = data.id;

                    if (url.id == 0) {
                        url.id = ids[0];
                    }

                    var info = {};
                    info.id = url.id;
                    var index = ids.indexOf(url.id);
                    info.name = fang[index] + '▾';

                    var html = juicer(fangs, info);
                    title.prepend(html);

                    loadData(url.id);

                    var values = [];
                    $.each(fang, function (index, item) {
                        values.push(item + '▾');
                    });
                    $('#picker').attr('value', info.name);

                    picker(fang, values);
                } else {
                    $.modal({
                        title: '温馨提示',
                        text: '顺风车是小区内认证业主互助共享服务',
                        buttons: [
                            {
                                text: '知道了',
                                onClick: function () {
                                    window.history.go(-1);
                                }
                            },
                            {
                                text: '前往认证',
                                bold: true,
                                onClick: function () {
                                    window.location.href = 'estate-manage.html';
                                }
                            }
                        ]
                    });
                }
            });
        }

        /**
         * 列表
         */
        function loadData(id) {
            common.ajax('GET', '/ride-sharing/index', {
                'id': id,
                'per-page': pageSize,
                'page': 1
            }, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    pages = data.pagination.pageCount;

                    var html = juicer(tpl, data),
                        topTpl = juicer(top, data.top);
                    container.append(html);
                    container.prepend(topTpl);

                    if (pages == 1) {
                        loadBack(id);
                    }
                } else {
                    var data = rsp.data.info,
                        topTpl = juicer(top, data.top);
                    container.prepend(topTpl);

                    if (data.top.info[0].length == 0 && data.top.info[1].length == 0) {
                        var template = "<h3 style='text-align: center;margin-top: .5rem;'>当前暂无顺风车行程!</h3>";
                        container.append(template);
                    }

                    page = 2;
                    pages = 0;
                    loadBack(id);
                }
            })
        }

        /**
         * 回顾
         */
        function loadBack(id) {
            common.ajax('GET', '/ride-sharing/back', {
                'id': id,
                'per-page': pageSize,
                'page': 1
            }, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    backs = data.pagination.pageCount;

                    var viewTpl = juicer(view, data);
                    container.append(viewTpl);

                    back = 2;
                    if (backs == 1) {
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                        return;
                    }
                } else {
                    $.detachInfiniteScroll($('.infinite-scroll'));
                    // 删除加载提示符
                    $('.infinite-scroll-preloader').remove();
                }
            });
        }

        /**
         * 选择楼盘
         */
        function picker(fang, values) {
            $("#picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                               <button class="button button-link pull-right close-picker font-white">确定</button>\
                               <h1 class="title font-white">选择小区</h1>\
                               </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: values,
                        displayValues: fang
                    }
                ],
                onClose: function () {
                    var str = $('#picker').val();
                    str = str.replace('▾', '');
                    var index = fang.indexOf(str);

                    window.location.href = 'freeride-list.html?id=' + ids[index];
                }
            });
        }

        /**
         * 用户是否阅读过用户协议
         */
        function preLoad() {
            common.ajax('GET', '/ride-sharing/has-agree', {}, true, function (rsp) {
                if (rsp.data.code == 103) {
                    $.popup('.popup-agreement');
                }
            })
        }

        /**
         * 无限滚动
         */
        setTimeout(1000);
        $(document).on('infinite', '.infinite-scroll', function () {
            if (loading) return;

            if (page > pages) {
                setTimeout(1000);
                if (loaded) return;

                if (back > backs) {
                    // 加载完毕，则注销无限加载事件，以防不必要的加载
                    $.detachInfiniteScroll($('.infinite-scroll'));
                    $('.infinite-scroll-preloader').remove();
                    return;
                }

                loaded = true;

                common.ajax('GET', '/ride-sharing/back', {
                    'id': url.id,
                    'per-page': pageSize,
                    'page': back
                }, false, function (rsp) {
                    if (rsp.data.code == 0) {
                        loaded = false;
                        var data = rsp.data.info;
                        backs = data.pagination.pageCount;

                        var viewTpl = juicer(view, data);
                        container.append(viewTpl);

                        if (backs == 1) {
                            $.detachInfiniteScroll($('.infinite-scroll'));
                            // 删除加载提示符
                            $('.infinite-scroll-preloader').remove();
                            return;
                        }

                        back++;
                    }
                });

                $.refreshScroller();
                return;
            }

            loading = true;

            common.ajax('GET', '/ride-sharing/index', {
                'id': url.id,
                'per-page': pageSize,
                'page': page
            }, false, function (rsp) {
                if (rsp.data.code == 0) {
                    loading = false;
                    var data = rsp.data.info;
                    pages = data.pagination.pageCount;

                    var html = juicer(tpl, data);
                    container.append(html);

                    page++;
                }
            });

            $.refreshScroller();
        });

        //点击跳转详情页
        $(document).on('click', '.freeride', function () {
            var self = $(this),
                id = self.data('id');

            if (url.id == 0) {
                url.id = ids[0];
            }
            window.localStorage.setItem('loupanId', url.id);

            window.location.href = "freeride-detail.html?id=" + id;
        });

        /**
         * 加入行程
         */
        $(document).on('click', '.ride-join', function () {
            var self = $(this),
                rs_id = self.parents('.ride-list-item').children('div:nth-child(1)').data('id');

            if (self.hasClass('font-grey')) {
                $.alert('很遗憾,乘客已满,无法搭车!');
                return;
            }

            //弹出层
            var modal = $.modal({
                title: '<span style="font-size: .7rem;color: #009042">请选择搭车人数</span>',
                text: '<div><button class="modal-num-btn">1</button>' +
                '<button class="modal-num-btn">2</button>' +
                '<button class="modal-num-btn">3</button>' +
                '<button class="modal-num-btn">4</button></div>',
            });
            $('.modal-overlay').on('click', function () {
                $.closeModal(modal);
            });

            $('.modal-num-btn').on('click', function () {
                var _self = $(this),
                    index = _self.index();

                common.ajax('POST', '/ride-sharing/join', {
                    'rs_id': rs_id,
                    'type': 1,
                    'customer_num': index + 1
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;
                        $.closeModal(modal);

                        $.alert('搭车预约成功!', function () {
                            //置顶
                            self.parent().parent().prependTo(container);
                            //滚动到顶部
                            $('.content').scrollTop();

                            var template = "<a class='button ride-btn font-grey ride-cancel'>取消搭车</a>";
                            self.parents('.ride-list-item').children('div:nth-child(1)').children('div:nth-child(2)').find('span').text('剩余' + data.params.seats + '座位');
                            self.replaceWith(template);
                        })
                    } else if (rsp.data.code == 111) {
                        $.closeModal(modal);
                        $.alert(rsp.data.message + ', 当前还剩余' + rsp.data.info + '个座位');
                    } else {
                        $.closeModal(modal);
                        $.alert('很抱歉,搭车失败,请重试!');
                    }
                })
            });
        });

        /**
         * 取消搭车
         */
        $(document).on('click', '.ride-cancel', function () {
            var self = $(this),
                rs_id = self.parents('div.ride-list-item').children('div:nth-child(1)').data('id');
            self.off('click');

            common.ajax('POST', '/ride-sharing/join', {
                'rs_id': rs_id,
                'type': 0
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('取消搭车成功', function () {
                        self.parent().parent().remove();
                        window.location.reload();
                    })
                } else {
                    $.alert('很抱歉,取消搭车操作失败,请重试!', function () {
                        window.location.reload();
                    });
                }
            });
        });

        /**
         * 取消行程
         */
        $(document).on('click', '.route-cancel', function () {
            var self = $(this),
                rs_id = self.parents('div.ride-list-item').children('div:nth-child(1)').data('id');

            self.off('click');

            $.confirm('您确认取消行程吗?', function () {
                common.ajax('POST', '/ride-sharing/status', {'rs_id': rs_id, 'type': 1}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('行程取消成功', function () {
                            window.location.reload();
                        })
                    } else {
                        $.alert('很抱歉,您的操作失败,请重试', function () {
                            window.location.reload();
                        })
                    }
                })
            });
        });

        /**
         * 乘客已满
         */
        $(document).on('click', '.custom-enough-green', function () {
            var self = $(this),
                rs_id = self.parents('.ride-list-item').children('div:nth-child(1)').data('id');

            self.off('click');

            $.confirm('您确认乘客已满了吗?', function () {
                common.ajax('POST', '/ride-sharing/status', {'rs_id': rs_id, 'type': 2}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        window.location.reload();
                    } else {
                        $.alert('很抱歉,您的操作失败,请重试', function () {
                            window.location.reload();
                        })
                    }
                })
            });
        });
        $(document).on('click', '.custom-enough-grey', function () {
            $.alert('很抱歉,人数已满,无法加入行程');
        });

        /**
         * 查看详情
         */
        $(document).on('click', '.ride-detail', function () {
            var self = $(this),
                rs_id = self.parents('div.freeride').data('id');

            if (url.id == 0) {
                url.id = ids[0];
            }
            window.localStorage.setItem('loupanId', url.id);

            window.location.href = 'freeride-detail.html?id=' + rs_id;
        });

        /**
         * 发布顺风车
         */
        $(document).on('click', '#create', function () {
            if (url.id == 0) {
                url.id = ids[0];
            }
            window.localStorage.setItem('loupanId', url.id);
            window.location.href = 'freeride-post.html';
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function () {
            window.location.href = common.ectouchPic;
        });

        /**
         * 提示信息
         */
        $(document).on('click', '.call-driver', function (event) {
            event.preventDefault();

            var self = $(this),
                href = self.attr('href');

            if (href == 'tel:') {
                $.alert('该车主未提供手机号码!');
            } else {
                window.location.href = href;
            }
        });

        /**
         * 感谢车主
         */
        $(document).on('click', '.to-thank', function () {
            var self = $(this),
                parent = self.parent(),
                id = parent.data('id');

            if (url.id == 0) {
                url.id = ids[0];
            }
            window.localStorage.setItem('loupanId', url.id);
            //感谢完返回列表页
            window.location.href = 'freeride-3q.html?id=' + id + '&back=1';
        });

        /**
         * 阅读协议
         */
        $(document).on('click', '#agree', function () {
            common.ajax('GET', '/ride-sharing/agree', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.closeModal('.popup-agreement');
                } else {
                    $.alert('很抱歉,同意协议失败,请重试!');
                }
            })
        });

        /**
         * 拒绝协议
         */
        $(document).on('click', '#reject', function () {
            window.history.go(-1);
        });

        preLoad();
        loadFang();

        var pings = env.pings;
        pings();
    });

    $.init();
});