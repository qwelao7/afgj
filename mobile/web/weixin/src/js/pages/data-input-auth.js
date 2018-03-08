require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-input-auth", function (e, id, page) {
        var url = common.getRequest(),
            data = {},
            data_submit = {},
            community = [],
            company = [];
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');


        // 跨域ajax请求

        function loadData() {
            $.ajax({
                url: env.ajax_data + "/pes/property-manager/permission?token=" + token,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        data = rsp.data;
                        $.each(data.project, function (index, item) {
                            community.push(item);
                        })
                        $.each(data.company, function (index, item) {
                            company.push(item);
                        })
                    }
                    picker1();
                    picker2();
                },
                error: function () {
                    alert('fail');
                }
            });
        };


        /**
         * 选择小区
         */
        function picker1() {
            $("#community").picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">请选择小区</h1></header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: community
                    }
                ],
                onOpen: function () {
                    var template = "<div class='modal-overlay modal-overlay-visible'></div>";
                    $('.page').append(template);
                },
                onClose: function () {
                    $(".modal-overlay").removeClass('modal-overlay-visible');
                    var str = $('#picker').val();
                    str = $.trim(str);
                }
            });
        }

        /**
         * 选择公司
         */
        function picker2() {
            $("#company").picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">请选择公司</h1></header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: company
                    }
                ],
                onOpen: function () {
                    var template = "<div class='modal-overlay modal-overlay-visible'></div>";
                    $('.page').append(template);
                },
                onClose: function () {
                    $(".modal-overlay").removeClass('modal-overlay-visible');
                    var str = $('#picker').val();
                    str = $.trim(str);
                }
            });
        }

        /**
         * 点击遮罩层
         */
        $(document).on('click', '.modal-overlay', function () {
            $(this).removeClass('modal-overlay-visible');
            $(".picker").picker("close");
            $('#modal-right').addClass('visibility-hidden');
        });

        // 提交表单
        $(page).on('click', '#submit', function () {
            var submit = data_submit.permission = {};
            submit.name = $('#name').val();
            submit.mobile = $('#mobile').val();
            submit.department = $('#department').val();
            submit.title = $('#title').val();
            $.each(data.project, function (index, item) {
                if ($('#community').val() == item) {
                    submit.projectCode = index;
                }
            })
            $.each(data.company, function (index, item) {
                if ($('#company').val() == item) {
                    submit.company = index;
                }
            })

            $.ajax({
                url: env.ajax_data + "/pes/property-manager/permission?token=" + token,
                type: 'POST',
                data: data_submit,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        $.alert('权限申请成功,请等待后台审核', function () {
                            window.location.href = 'data-address-list.html';
                        });
                    } else {
                        $.alert(rsp.msg, function () {
                            window.location.href = 'data-address-list.html';
                        });
                    }

                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            })
        });

        //返回
        $(page).on('click', '#back', function () {
            history.go(-1);
        });


        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});
