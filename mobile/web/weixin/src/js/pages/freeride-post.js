require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#freeride-post", function (e, id, page) {
        var loupanId = localStorage.getItem('loupanId');
        if(!loupanId) loupanId = 0;

        /**
         * 参数
         */
        var tpl = $('#tpl').html(),
            panel = $('#panel');
        var items = {},
            cars = {},
            names = [],
            ids = [],
            time;

        function loadData() {
            common.ajax('GET', '/ride-sharing/account-car', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    time = rsp.data.info.time;

                    var html = juicer(tpl, data);
                    panel.prepend(html);

                    $.each(data.list, function(index, item) {
                        names.push(item.car_num);
                        ids.push(item.id);
                    });
                    cars.names = names;cars.ids = ids;

                    picker(time);
                    pickerCar(cars);
                } else if (rsp.data.code == 110) {
                    var path = '?refer=freeride&type=1';
                    window.location.href = 'freeride-setcar.html' + path;
                } else {
                    $.alert('很抱歉,服务器失去连接,请重试');
                }
            });
        }

        loadData();

        /**
         * 设置触发时间
         */
        function picker(time) {
            var today = common.formatDate(parseInt(time)),
                tomorrow = common.formatDate(parseInt(time) + 86400),
                afterTom = common.formatDate(parseInt(time) + 86400 * 2);
            var now = new Date();
            var time1 = now.getTime() + 1000 * 60 * 30,
                day = new Date(time1);
            var dayYear = day.getFullYear(),
                dayMonth = day.getMonth() + 1,
                dayDate = day.getDate();
            dayMonth = (dayMonth < 10) ? '0' + dayMonth : dayMonth;
            dayDate = (dayDate < 10) ? '0' + dayDate : dayDate;

            var hour = new Date(time1).getHours() + ':',
                minute = (parseInt(new Date(time1).getMinutes() / 5) * 5)<10?'0'+(parseInt(new Date(time1).getMinutes() / 5) * 5):parseInt(new Date(time1).getMinutes() / 5) * 5;
            var str= dayYear + '-' + dayMonth + '-' + dayDate + ' '+ hour + ' ' + minute;
             $('.pickertime').val(str);

            $(".pickertime").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                              <button class="button button-link pull-right close-picker font-white">确定</button>\
                              <h1 class="title font-white">请选择出发时间</h1>\
                              </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: [today, tomorrow, afterTom],
                        displayValues: ['今天', '明天', '后天']
                    },
                    {
                        textAlign: 'center',
                        values: ['00:', '01:', '02:', '03:', '04:', '05:', '06:', '07:', '08:', '09:', '10:', '11:', '12:', '13:', '14:', '15:', '16:', '17:', '18:', '19:', '20:', '21:', '22:', '23:'],
                        displayValues: ['0时', '1时', '2时', '3时', '4时', '5时', '6时', '7时', '8时', '9时', '10时', '11时', '12时', '13时', '14时', '15时', '16时', '17时', '18时', '19时', '20时', '21时', '22时', '23时']
                    },
                    {
                        textAlign: 'center',
                        values: ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'],
                        displayValues: ['0分', '5分', '10分', '15分', '20分', '25分', '30分', '35分', '40分', '45分', '50分', '55分']
                    }
                ],
                onClose: function () {
                    var self = $('.pickertime'),
                        text = self.val(),
                        stamp = new Date(text).getTime();

                    if (stamp <= parseInt(time) * 1000) {
                        $.alert('很抱歉,出发时间不能小于当前时间');
                        self.val('');
                        return;
                    }
                },
                onOpen: function () {

                }
            });
        }

        /**
         * 设置剩余座位
         */
        $(".pickerseat").picker({
            toolbarTemplate: '<header class="bar bar-nav">\
                              <button class="button button-link pull-right close-picker font-white">确定</button>\
                              <h1 class="title font-white">请选择剩余座位数</h1>\
                              </header>',
            cols: [
                {
                    textAlign: 'center',
                    values: ['1位', '2位', '3位', '4位']
                }
            ]
        });

        /**
         * 选择车辆
         */
        function pickerCar(cars) {
            $('#pickerCar').picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                              <button class="button button-link pull-right close-picker font-white">确定</button>\
                              <h1 class="title font-white">请选择车辆</h1>\
                              </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: cars.names
                    }
                ],
                onClose: function() {
                    var str = $('#pickerCar').val(),
                        index = cars.names.indexOf(str),
                        id = cars.ids[index];

                    $('#pickerCar').data('id', id);
                }
            })
        }

        /**
         * 提交顺风车信息
         */
        $(page).on('click', '#submit', function (event) {
            event.preventDefault();
            var self = $(this),
                params = {};
            params.arr = [];
            params.err = '';
            self.prop("disabled", true);

            items.loupan_id = loupanId;
            items.go_time = $('#go_time').val();
            items.origin = $('#origin').val();
            items.destination = $('#destination').val();
            items.leave_seat = $('#leave_seat').val();
            items.wish_message = $('#wish_message').val();
            items.car_id = $('#pickerCar').data('id');

            tips(items.leave_seat, '请选择您的剩余座位', self, params);
            tips(items.destination, '请填写您的目的地点', self, params);
            tips(items.origin, '请填写你的出发地点', self, params);
            tips(items.go_time, '请选择您的出发时间', self, params);
            tips(items.car_id, '请选择您的车辆', self, params);

            items.leave_seat = items.leave_seat.replace('位', '');
            items.go_time = items.go_time.replace(/\-/g, '/');
            items.go_time = new Date(items.go_time).getTime();

            if (params.arr.indexOf('false') == -1) {
                common.ajax('POST', '/ride-sharing/post', {'data': items}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('您的行程已发布!', function () {
                            window.location.href = 'freeride-post-success.html';
                        });
                    } else {
                        $.alert('您的行程提交失败,请重试', function () {
                            self.prop("disabled", false);
                        });
                    }
                })
            } else {
                params.arr = [];
                $.alert(params.err, function () {
                    self.prop("disabled", false);
                });
            }

        });

        function tips(selecter, tips, self, params) {
            if (selecter == "" || selecter == undefined) {
                params.arr.push('false');
                params.err = tips;
                return params;
            }
        }

        /**
         * 返回
         */
        $(document).on('click', '#back', function(){
            window.location.href = 'freeride-list.html?id=' + loupanId;
        })

        var pings = env.pings;pings();
    });

    $.init();
});
