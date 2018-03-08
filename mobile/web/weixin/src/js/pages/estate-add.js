require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#estate-add', function (e, id, page) {
        //type 1:新建小区  0: 已拥有小区

        var container = $('#container'),
            tpl = $('#tpl').html(),
            ext = $('#ext').html(),
            region_id = 0;

        var status = true,
            transArr = [],
            cnArr = [],
            key;

        /**
         * 新建小区 url必须带上城市id
         */
        var url = common.getRequest();

        function loadData() {
            if (url.type == 1) {
                loadNew();
            } else if (url.type == 0) {
                loadCommunity();
            }
        }

        function loadNew() {
            if (!url.id || url.id == undefined) {
                $.alert('很抱歉,地址出错,请重试!', '数据错误', function () {
                    window.history.go(-1);
                });
                return false;
            }

            /**
             * 渲染模板
             */
            var html = juicer(tpl, {});
            container.append(html);

            common.ajax('GET', '/site/region', {'regionType': 3, 'parentId': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        regionName = [],
                        regionId = [];

                    data.forEach(function (item, index) {
                        regionName.push(item.region_name);
                        regionId.push(item.region_id);
                    });

                    pick(regionName, regionId);
                } else {
                    $.alert('暂无行政区数据,请重试', function () {
                        window.location.href = 'city-list.html';
                    })
                }
            });
        }

        function loadCommunity() {
            var community = window.localStorage.getItem('community');
            community = JSON.parse(community);

            common.ajax('GET', '/house/filter', {'id': community.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        info = {};
                    info.arr = data;
                    info.community = community;

                    var htm = juicer(ext, info);
                    container.append(htm);
                } else {
                    $.alert('很抱歉,暂无小区信息', function () {
                        window.location.href = 'estate-manage.html';
                    });
                }
            })
        }

        /**
         *  renderPicker
         */
        function pick(params, arr) {
            $("#picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">选择行政区</h1>' +
                '</header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: params
                    }
                ],
                onClose: function () {
                    var str = $('#picker').val(),
                        index = params.indexOf(str);

                    if (index != -1) {
                        region_id = arr[index];
                    }
                }
            });
            $('#picker').val(params[0]);
            region_id = arr[0];
        }

        function skipHref(data, type) {
            $.modal({
                title: '温馨提示',
                text: '您的房产创建成功,请前往房产认证界面',
                buttons: [
                    {
                        text: '知道了',
                        onClick: function () {
                            window.location.href = 'estate-manage.html';
                        }
                    },
                    {
                        text: '立即认证',
                        bold: true,
                        onClick: function () {
                            window.location.href = 'estate-auth.html?id=' + data + '&type=' + type;
                        }
                    }
                ]
            });
        }

        function validate(params) {
            for(var i in params) {
                if (params[i] == '' || params[i] == undefined) {
                    key = transArr.indexOf(i);
                    if (key != -1) {
                        $.alert('很抱歉,' + cnArr[key] + '不能为空!', '验证失败', function() {
                            status = true;
                        });
                        return false;
                    }
                }
            }
            return true;
        }

        function updateParams(params) {
            if (url.type == 0) {
                params['community_id'] = $('.community-name').data('id');
                transArr = ['building_num'];
                cnArr = ['楼栋号'];
            } else if (url.type == 1) {
                transArr = ['building_num', 'community_name'];
                cnArr = ['楼栋号', '小区名称'];
                delete params['district_id'];
            }

            params['is_default'] = (params['is_default'] === 'on') ? 'yes' : 'no';
        }

        function submit (params) {
            if (url.type == 0) {
                common.ajax('POST', '/house/create', {
                    'frmHouse': params
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        skipHref(rsp.data.info, 1);
                    } else if (rsp.data.code == 119) {
                        $.alert('您已拥有该房产。', '创建失败', function () {
                            status = true;
                        })
                    } else {
                        $.alert('房产创建失败,请重试!', '创建失败', function () {
                            status = true;
                        })
                    }
                })
            } else if (url.type == 1) {
                common.ajax('POST', '/house/create-by-user', {
                    'frmHouse': params,
                    'district_id': region_id
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        skipHref(rsp.data.info, 2);
                    } else if (rsp.data.code == 119) {
                        $.alert('您已拥有该房产。', '创建失败', function () {
                            status = true;
                        })
                    } else {
                        $.alert('房产创建失败,请重试!', '创建失败', function () {
                            status = true;
                        })
                    }
                })
            }
        }

        /**
         * 收取/展示隐藏内容
         */
        $(page).on('click', '#more', function () {
            $(this).replaceWith('<h3 style="margin-top: .5rem;" class="font-grey lr-padding" id="nomore">隐藏显示</h3>');
            $('#except').toggleClass('visibility-hidden');
        });
        $(page).on('click', '#nomore', function () {
            $(this).replaceWith('<h3 style="margin-top: .5rem;" class="font-grey lr-padding" id="more">显示更多房产字段</h3>');
            $('#except').toggleClass('visibility-hidden');
        });

        /**
         * 提交表单
         */
        $(document).on('click', '#submit', function () {
            if (!status) return false;
            status = false;

            // init params
            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            updateParams(params);

            if (validate(params)) {
                // 特殊校验
                if (region_id == 0 && url.type == 1) {
                    $.alert('很抱歉, 请选择行政区!', '验证失败', function() {
                        status = true;
                    });
                    return false;
                }

                if (params['mobile'] !== '') {
                    if (!common.check(params.mobile, 2) && !common.check(params.mobile, 3)) {
                        $.alert('很抱歉, 请输入正确的手机号码!', '验证失败', function() {
                            status = true;
                        });
                        return false;
                    }
                }

                submit(params);
            }
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});