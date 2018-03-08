require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#freeride-detail", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            tools = $('#tools').html(),
            add = $('#add').html(),
            nav = $('#nav'),
            container = $('#container');

        var format = function (data) {
            data = data.replace(/\-/g, '/');
            var cur = new Date(),
                curTime = cur.getFullYear() + '/' + (cur.getMonth()+1) + '/' + cur.getDate() + ' ' + '00:00:00',
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

        function loadData() {
            common.ajax('GET', '/ride-sharing/detail', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        val = {};
                    val['info'] = rsp.data.info['params'];

                    var html = juicer(tpl, data),
                        htmlT = juicer(tools, val);

                    container.append(html);
                    nav.append(htmlT);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无相关数据!</h3>";
                    container.append(template);
                    nav.remove();
                }
            });
        }

        loadData();

        /**
         * 取消行程
         */
        $(document).on('click', '#cancel-route', function () {
            $(this).off('click');
            $.confirm('您确认取消行程吗?', function () {
                common.ajax('POST', '/ride-sharing/status', {'rs_id': url.id, 'type': 1}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        var loupanId = window.localStorage.getItem('loupanId');
                        if(!loupanId) loupanId = 0;
                        window.location.href = 'freeride-list.html?id=' + loupanId;
                    } else {
                        $.alert('很抱歉,您的操作失败,请重试', function () {
                            window.location.reload();
                        })
                    }
                })
            });
        });

        /**
         * 终止乘客
         */
        $(document).on('click', '#cancel-enough', function () {
            var self = $(this),
                seats = self.data('seats');
            if (seats == 0) {
                $.alert('乘客已满,无需操作!');
                return;
            }

            $(this).off('click');
            $.confirm('您确认乘客已满了吗?', function () {
                common.ajax('POST', '/ride-sharing/status', {'rs_id': url.id, 'type': 2}, true, function (rsp) {
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

        /**
         *  搭车
         */
        $(document).on('click', '#to-join', function () {
            var self = $(this),
                seats = self.data('seats');
            if (seats == 0) {
                $.alert('很抱歉,乘客已满,无法搭车!');
                return;
            }

            var modal = $.modal({
                title: '<span style="font-size: .7rem;color: #009042">请选择搭车人数</span>',
                text: '<div><button class="modal-num-btn">1</button>' +
                '<button class="modal-num-btn">2</button>' +
                '<button class="modal-num-btn">3</button>' +
                '<button class="modal-num-btn">4</button></div>',
            });
            $(document).on('click', '.modal-overlay', function () {
                $.closeModal(modal);
            });
            $(document).one('click', '.modal-num-btn', function () {
                var index = $(this).index();
                $.closeModal(modal);
                $('.modal-num-btn').remove();
                getCar(index, self);
            })
        });

        function getCar(index, self) {
            common.ajax('POST', '/ride-sharing/join', {
                'rs_id': url.id,
                'type': 1,
                'customer_num': index + 1
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    var template = "<a class='tab-item external cancel' id='cancel-join'>" +
                        "<span class='font-white'>取消搭车</span>" +
                        "</a>";
                    self.replaceWith(template);

                    var seats = $('#seats');
                    seats.text('剩余' + data.params.seats + '座位');

                    var html = juicer(add, data);
                    container.append(html);
                } else if (rsp.data.code == 111) {
                    $.alert(rsp.data.message + ', 当前还剩余' + rsp.data.info + '个座位');
                } else {
                    $.alert('很抱歉,搭车失败,请重试!', function () {
                        window.location.reload();
                    });
                }
            })
        }

        /**
         * 取消搭车
         */
        $(document).on('click', '#cancel-join', function () {
            var self = $(this);
            self.off('click');

            common.ajax('POST', '/ride-sharing/join', {
                'rs_id': url.id,
                'type': 0
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('取消搭车成功!', function() {
                        var data = rsp.data.info,
                            template = "<a class='tab-item external white' id='to-join'>" +
                                "<span class='font-black'>我要搭车</span>" +
                                "</a>";
                        self.replaceWith(template);

                        if (data.isCancel) {
                            $('.rs-members').remove();
                        } else {
                            var arr = [];
                            $('.order-member').each(function (index, item) {
                                arr.push($(item).data('uid'));
                            });
                            var index = arr.indexOf(data.u_id);
                            $('.order-member').eq(index).remove();
                        }

                        var seats = $('#seats');
                        seats.text('剩余' + data.seats + '座位');
                    })
                } else {
                    $.alert('很抱歉,取消搭车操作失败,请重试!', function () {
                        window.location.reload();
                    });
                }
            });
        });

        /**
         * 感谢车主
         */
        $(document).on('click', '#to-thank', function () {
            window.location.href = 'freeride-3q.html?id=' + url.id;
        });

        /**
         * 拨打电话
         */
        $(document).on('click', '#call-driver', function (event) {
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
         * 返回
         */
        $(document).on('click', '#back', function() {
            var loupanId = window.localStorage.getItem('loupanId');
            if(!loupanId) loupanId = 0;
            window.location.href = 'freeride-list.html?id=' + loupanId;
        });

        var pings = env.pings;pings();
    });

    $.init();
});
