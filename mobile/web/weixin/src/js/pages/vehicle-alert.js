require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#vehicle-alert", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            list = $('#list').html(),
            container = $('#container');

        var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据!</h3>";

        var status = true,
            params = {},
            cur = '',
            key,
            last_km;

        var modal_last_date = '',
            modal_last_km = '';

        var transArr = ['last_km', 'last_date', 'next_km', 'next_date'],
            chineseArr = ['最新保养里程数', '最新保养时间', '下次间隔公里数', '下次间隔月份'],
            skipArr = [];

        var trans = function (data) {
            return data.replace(/[\-]/g, '.')
        };
        juicer.register('trans', trans);

        function loadData() {
            common.ajax('GET', '/vehicle/detail', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data),
                        htm = juicer(list, data);

                    last_km = parseInt(data.base.now_km);
                    cur = new Date(data.cur).getTime();
                    container.append(html);
                    container.append(htm);
                } else {
                    container.append(template);
                }
            })
        }

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

            //隐藏弹出层
            $.closeModal();
            status = true;
        });

        /**
         * 编辑车辆信息
         */
        $('#edit-car').live('click', function () {
            var path = '?type=2&refer=vehicle&refer_id=' + url.id;

            location.href = 'freeride-setcar.html' + path;
        });

        /**
         * 删除车辆信息
         */
        $('#delete-car').live('click', function () {
            $('#popup').css('display', 'none');
            $('#modal').removeClass('modal-overlay-visible');
            $.confirm('您确认删除车辆?',
                function () {
                    deleteCar();
                },
                function () {
                    console.log('点击了取消按钮');
                }
            );
        });

        /**
         * 更新里程数
         */
        $('#miles-update').live('click', function () {
            $.modal({
                title: '<span class="h2 font-green">更新里程</span>',
                text: '<div>' +
                '<p class="vehicle-modal-text"><span>当前里程数</span><input type="text" name="now-kw"/></p>' +
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
                            var value = $.trim($('input[name=now-kw]').val());

                            if (parseInt(value) < last_km) {
                                $.alert('很抱歉,您更新的里程数小于当前里程数!', '更新失败');
                            } else {
                                updateKm(value);
                            }
                        }
                    }
                ]
            })
        });

        /**
         * 保养
         */
        // $('.vehicle-list-left').live('click', function () {
        //     if (!status) return false;
        //     status = false;
        //
        //      var self = $(this),
        //          id = self.data('id'),
        //          name = self.data('name');
        //
        //     common.ajax('GET', '/vehicle/ajax-get-notification', {'carId': url.id, 'tipId': id}, true, function (rsp) {
        //         if (rsp.data.code == 0) {
        //             var data = rsp.data.info;
        //
        //             modal_last_date = data.base.last_date;
        //             modal_last_km = data.base.last_km;
        //             renderModal(data.base, data.cur, name, id);
        //         } else {
        //             $.alert('很抱歉,获取车辆提醒信息失败', '获取信息失败');
        //             status = true;
        //         }
        //     });
        // });
        $('.vehicle-list-left').live('click', function () {
            var self = $(this),
                id = self.data('id'),
                path = '?id=' + id + '&car_id=' + url.id;

            location.href = 'vehicle-maintenance-add.html' + path;
        });

        /**
         * 删除车辆提醒
         */
        $('.vehicle-delete').live('click', function () {
            var self = $(this),
                parent = self.parent(),
                id = self.data('id');

            $.confirm('您确认删除该车辆提醒?',
                function () {
                    deleteRemain(id, parent);
                });
        });

        /**
         * 记录
         */
        $('#car-record').live('click', function () {
            location.href = 'vehicle-maintenance-record.html?car_id=' + url.id;
        });

        /**
         * 返回
         */
        $('#back').live('click', function () {
            location.href = 'vehicle-manage.html';
        });

        /**
         * 新建提醒
         */
        $('#add').live('click', function () {
            var path = '?id=' + url.id;
            location.href = 'vehicle-alert-add.html' + path;
        });

        /**
         * 限制输入
         */
        $(document).on('keyup', 'input[type=number]', function() {
            var _this = $(this),
                reg = _this.val().match(/\d+/),
                txt = '';
            if (reg != null)
            {
                txt = reg[0];
            }
            _this.val(txt);
        });

        //删除车辆
        function deleteCar() {
            common.ajax('GET', '/vehicle/delete', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    location.href = 'vehicle-manage.html';
                } else {
                    $.alert('很抱歉,车辆删除失败,失败原因: ' + rsp.data.message, '车辆删除失败');
                }
            })
        }

        //删除提醒
        function deleteRemain(id, parent) {
            common.ajax('GET', '/vehicle/delete-remind', {'id': id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    parent.remove();
                } else {
                    $.alert('很抱歉,删除车辆提醒失败,失败原因: ' + rsp.data.message, '删除车辆提醒失败')
                }
            })
        }

        //更新里程数
        function updateKm(value) {
            common.ajax('GET', '/vehicle/update-km', {'id': url.id, 'now_km': value}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    $('#now-kw-show').text(value + 'km');
                    $('#update_time').text('（更新于' + data.date + '）');
                    
                    renderList();
                } else {
                    $.alert('很抱歉,更新里程失败,失败原因:' + rsp.data.message, '更新失败')
                }
            })
        }
        
        //重新渲染提醒列表
        function renderList () {
            common.ajax('GET', '/vehicle/ajax-update-list', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        tem = juicer(list, data);

                    $('#content').empty().append(tem);
                } else {
                    $.alert('更新车辆提醒失败,请重试!', '更新失败', function () {
                        location.reload();
                    })
                }
            })
        }

        //渲染弹出层
        function renderModal(data, cur, name, id) {
            $.modal({
                title: '<span class="h2 font-green">更新' + name + '提醒</span>',
                text: renderText(data, cur),
                buttons: [
                    {
                        text: '取消',
                        onClick: function () {
                            $('#modal').removeClass('modal-overlay-visible');
                            status = true;
                            console.log('click cancel');
                        }
                    },
                    {
                        text: '确定',
                        bold: true,
                        onClick: function () {
                            params = common.formToJson($('form').serialize());
                            params = JSON.parse(decodeURIComponent(params));

                            var valid = validate(params);

                            if (valid) {
                                params['car_id'] = url.id;
                                params['id'] = id;
                                submit(params);
                            }
                            status = true;
                        }
                    }
                ]
            });

            $("input[name=last_date]").calendar({
                value: [cur]
            });
        }

        //渲染弹出层内容
        function renderText (data, cur) {
            var text = '';

            if (data.next_km != 0 && data.next_month != 0) {
                text = '<form class="render_form">' +
                    '<p class="vehicle-modal-text">' +
                    '<span>最新保养里程数</span>' +
                    '<input type="number" name="last_km" value="' + data.last_km + '"  style="-webkit-appearance:none;outline:none;appearance:none;"/>' +
                    '</p>' +
                    '<p class="vehicle-modal-text">' +
                    '<span>最新保养时间</span>' +
                    '<input type="text" name="last_date" value="' + cur + '" data-toggle="date" readonly  style="-webkit-appearance:none;outline:none;appearance:none;"/>' +
                    '</p>' +
                    '<p class="vehicle-modal-text">' +
                    '<span>下次间隔公里数</span>' +
                    '<input type="number" name="next_km" value="' + data.next_km + '"  style="-webkit-appearance:none;outline:none;appearance:none;"/>' +
                    '</p>' +
                    '<p class="vehicle-modal-text">' +
                    '<span>下次间隔月份</span>' +
                    '<input type="number" name="next_month" value="' + data.next_month + '"  style="-webkit-appearance:none;outline:none;appearance:none;"/>' +
                    '</p>' +
                    '</form>';
            } else if (data.next_km == 0 && data.next_month != 0) {
                text = '<form class="render_form">' +
                    '<p class="vehicle-modal-text">' +
                    '<span>最新保养时间</span>' +
                    '<input type="text" name="last_date" value="' + cur + '" data-toggle="date" readonly  style="-webkit-appearance:none;outline:none;appearance:none;"/>' +
                    '</p>' +
                    '<p class="vehicle-modal-text">' +
                    '<span>下次间隔月份</span>' +
                    '<input type="number" name="next_month" value="' + data.next_month + '"  style="-webkit-appearance:none;outline:none;appearance:none;"/>' +
                    '</p>' +
                    '</form>';
            } else if (data.next_km != 0 && data.next_month == 0) {
                text = '<form class="render_form">' +
                    '<p class="vehicle-modal-text">' +
                    '<span>最新保养里程数</span>' +
                    '<input type="number" name="last_km" value="' + data.last_km + '"  style="-webkit-appearance:none;outline:none;appearance:none;"/>' +
                    '</p>' +
                    '<p class="vehicle-modal-text">' +
                    '<span>下次间隔公里数</span>' +
                    '<input type="number" name="next_km" value="' + data.next_km + '"  style="-webkit-appearance:none;outline:none;appearance:none;"/>' +
                    '</p>' +
                    '</form>';
            }

            return text;
        }

        //验证form表单
        function validate (params) {
            //检验参数是否为空
            for(var i in params) {
                if (skipArr.indexOf(i) == -1) {
                    if (params[i] == '' || params[i] == undefined) {
                        key = transArr.indexOf(i);
                        $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败');
                        return false;
                    }
                }
            }

            //特殊校验
            if (parseInt(params['last_km']) < parseInt(modal_last_km)) {
                $.alert('很抱歉,您填写的里程数小于上次保养里程数,请填写正确的里程!', '验证失败');
                return false;
            }

            if (new Date(modal_last_date).getTime() > new Date(params['last_date']).getTime()) {
                $.alert('很抱歉,您填写的保养日期小于上次保养日期,请填写正确的保养日期!', '验证失败');
                return false;
            }
            return true;
        }

        //提交
        function submit(params) {
            common.ajax('POST', '/vehicle/remind', params, true, function (rsp) {
                if (rsp.data.code == 0) {
                    renderList();
                } else {
                    $.alert('很抱歉,更新失败,失败原因: ' + rsp.data.message);
                }
            })
        }

        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});
