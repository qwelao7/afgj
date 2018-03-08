require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#error-add", function (e, id, page) {
        var url = common.getRequest();

        var params = {},
            imgArr = [],
            status = true,
            arr = ['decorate_id', 'material_id', 'failure_cause', 'contact_name', 'contact_phone'],
            transArr = ['装修项目', '故障材料', '故障原因', '联系人', '联系电话'],
            key;

        var items = ['#addressPicker', '#catePicker', '#matPicker', '#decoratePicker', '#errorPicker'];

        var typeArr = [],
            showOther = false; // 控制其他原因 元素是否显示

        function initParams() {
            params.d_id = url.d_id;
            params.c_id = url.c_id;
            params.m_id = url.m_id;
            params.address_id = url.address_id;
            params.type_id = 0;
            for(var i in items) {
                params[items[i]] = {
                    names: [],
                    ids: [],
                    name: ''
                }
            }
        }

        //渲染地址
        function renderAddress() {
            common.ajax('GET', '/feedback/decorate-address', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    if (!data.address) {
                        failHandler();
                        return false;
                    }

                    renderPicker(data.address, data.address_id, '#addressPicker', '房产');
                } else {
                    failHandler();
                }
            });
        }

        //decorate_id 装修项目id
        function renderCat(decorate_id, callback) {
            common.ajax('GET', '/feedback/decorate-cate', {
                'id': decorate_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    //params 处理
                    if (params.c_id == 0) params.c_id = data.cate_id[0];

                    callback(params.d_id, params.c_id, renderError);
                    renderPicker(data.name, data.cate_id, '#catePicker', '故障材料分类');
                } else {
                    failHandler();
                }
            })
        }

        //decorate_id 装修项目id
        //cat_id 装修材料分类id
        function renderMat(decorate_id, cat_id, callback) {
            common.ajax('GET', '/feedback/decorate-material', {
                'decorate_id': decorate_id,
                'cate_id': cat_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    // params 处理
                    params.type_id = data.type_id[0];

                    callback(params.type_id);
                    renderPicker(data.name, data.material_id, '#matPicker', '故障材料');

                    // 特殊处理
                    typeArr = data.type_id;
                } else {
                    failHandler();
                }
            })
        }

        //渲染装修项目
        function renderDeco(address_id, callback) {
            common.ajax('GET', '/feedback/decorate-project', {
                'id': address_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    //params 处理
                    if (params.d_id == 0) params.d_id = data.id[0];

                    callback(params.d_id, renderMat);
                    renderPicker(data.title, data.id, '#decoratePicker', '装修项目');
                } else {
                    failHandler();
                }
            })
        }

        //渲染错误原因
        function renderError(type_id) {
            common.ajax('GET', '/feedback/maintain-type', {
                'id': type_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    renderPicker(data, [], '#errorPicker', '故障原因');
                } else {
                    failHandler();
                }
            })
        }

        //页面初始化
        function init() {
            initParams();
            getConfig();
            renderAddress();
            updateRender(4);
        }

        //接口故障处理
        function failHandler() {
            $.alert('获取数据失败!', function () {
                backUrl();
            });
        }

        //backurl
        function backUrl() {
            var path = '?id=' + url.d_id + '&address_id=' + url.address_id + '&type=3';
            location.href = 'decoration-detail.html' + path;
        }

        //renderPciker
        function renderPicker(names, ids, pickers, str) {
            var id,
                name,
                index;

            switch (pickers) {
                case '#addressPicker':
                case '#catePicker':
                case '#matPicker':
                case '#decoratePicker':
                case '#errorPicker':
                    if (pickers == '#addressPicker') {
                        id = params.address_id;
                    } else if (pickers == '#catePicker') {
                        id = params.c_id;
                    } else if (pickers == '#matPicker') {
                        id = params.m_id;
                    } else if (pickers == '#decoratePicker') {
                        id = params.d_id;
                    } else if (pickers == 'pickers') {
                        id = params.type_id;
                    }


                    index = ids.indexOf(id);
                    if (index != -1) {
                        name = names[index];
                    } else {
                        name = names[0];
                    }

                    //params
                    params[pickers]['names'] = names;
                    params[pickers]['ids'] = ids;
                    params[pickers]['name'] = name;

                    $(pickers).val(name);

                    break;
            }

            $(pickers).picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                   <button class="button button-link pull-right close-picker font-white">确定</button>\
                                   <h1 class="title font-white">请选择' + str + '</h1>\
                                   </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: params[pickers]['names']
                    }
                ],
                onOpen: function (picker) {
                    picker.cols[0].replaceValues(params[pickers]['names']);

                    picker.setValue([params[pickers]['name']]);

                    picker.updateValue();
                },
                onClose: function (picker) {
                    params[pickers]['name'] = $(pickers).val();

                    clickHandler(pickers, params[pickers]['names'], params[pickers]['ids']);
                }
            });
        }

        function clickHandler(picker, names, ids) {
            switch (picker) {
                case '#addressPicker':
                    var ad_val = $(picker).val(),
                        ad_index = names.indexOf(ad_val);

                    params.address_id = ids[ad_index];
                    params.d_id = 0;
                    params.c_id = 0;

                    updateRender(4);
                    resetOther();

                    break;
                case '#decoratePicker':
                    var de_val = $(picker).val(),
                        de_index = names.indexOf(de_val);

                    params.d_id = ids[de_index];
                    params.c_id = 0;

                    updateRender(3);
                    resetOther();

                    break;
                case '#catePicker':
                    var ca_val = $(picker).val(),
                        ca_index = names.indexOf(ca_val);

                    params.c_id = ids[ca_index];

                    updateRender(2);
                    resetOther();

                    break;
                case '#matPicker':
                    var ma_val = $(picker).val(),
                        ma_index = names.indexOf(ma_val);

                    params.type_id = typeArr[ma_index];

                    updateRender(1);
                    resetOther();

                    break;
                case '#errorPicker':
                    var er_val = $(picker).val(),
                        er_index = er_val.indexOf('其他');

                    // 选择是否显示其他原因
                    if (er_index != -1) {
                        showOther = true;
                        $('#other_reason').removeClass('hide');
                    } else {
                        resetOther();
                    }

                    break;
            }
        }

        function resetOther () {
            showOther = false;
            $('#other_reason').addClass('hide');
        }

        function updateRender(num) {
            if (num == 4) {
                renderDeco(params.address_id, renderCat);
            } else if (num == 3) {
                renderCat(params.d_id, renderMat);
            } else if (num == 2) {
                renderMat(params.d_id, params.c_id, renderError);
            } else if (num == 1) {
                renderError(params.type_id);
            }
        }

        /**
         * 获取微信配置
         */
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
                            'chooseImage',
                            'previewImage',
                            'uploadImage',
                            'downloadImage'
                        ]
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        function renderPics(data) {
            var template = "<div class='col-33 box' style='position: relative;padding: .3rem 0;'>" +
                "<img src='" + data +"' style='width: 4rem;height: auto;max-height: 4rem;'>" +
                "<i class='iconfont icon-cancel delete' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                "</div>";

            $('.row').append(template);
            data = data.replace(common.QiniuDamain, '');
            imgArr.push(data);
        }

        function valid(query) {
            //验证是否为空
            for (var i in query) {
                key = arr.indexOf(i);
                if (key != -1) {
                    if (query[i] == '' || query[i] == 0) {
                        $.alert('很抱歉,' + transArr[key] + '不能为空!', '验证失败', function () {
                            status = true;
                        });
                        return false;
                    }
                }
            }

            // 特殊校验
            if (showOther) {
                if (query['other'] == '' || query['other'] == undefined) {
                    $.alert('很抱歉,请描述故障其他原因!', '验证失败', function () {
                        status = true;
                    });
                    return false;
                }
            }

            //校验手机号
            if (!common.check(query.contact_phone, 2) && !common.check(params.contact_phone, 3)) {
                $.alert('很抱歉,请填写正确的联系电话!', '验证失败', function() {
                    status = true;
                });
                return false;
            }

            return true;
        }

        init();

        $('#back').on('click', function () {
            backUrl();
        });

        /**
         * 上传图片
         */
        $(document).on('click', '.icon-camera', function () {
            wx.chooseImage({
                count: 1, // 默认9
                sizeType: ['compressed'], // 可以指定是原图还是压缩图，默认二者都有
                sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
                success: function (res) {
                    var localId = res.localIds[0]; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
                    wx.uploadImage({
                        localId: localId, // 需要上传的图片的本地ID，由chooseImage接口获得
                        success: function (res) {
                            var serverId = res.serverId; // 返回图片的服务器端ID
                            common.ajax('GET', '/wechat/upload', {mediaId: serverId}, true, function (rsp) {
                                if (rsp.data.code == 0) {
                                    var data = rsp.data.info;
                                    renderPics(data);
                                } else {
                                    $.alert('图片上传失败,请重试!', '上传失败');
                                }
                            })
                        }
                    });
                }
            });
        });

        /****
         * 删除图片
         * @type {module.exports.pings}
         */
        $(document).on('click', '.icon-cancel', function () {
            var self = $(this),
                parent = self.parent(),
                index = parent.index();
            parent.remove();
            imgArr.splice(index, 1);
        });

        /**
         * 提交
         * @type {module.exports.pings}
         */
        $(document).on('click', '#submit', function () {
            if (!status) return false;
            status = true;

            var query = common.formToJson($('form').serialize());
            query = JSON.parse(decodeURIComponent(query));

            query['decorate_id'] = params.d_id;
            query['material_id'] = params.m_id;
            query['failure_pics'] = (imgArr.length > 0) ? imgArr.join(',') : '';

            if (valid(query)) {
                // 特殊参数调整
                query['failure_cause'] = (showOther) ? query['other'] : query['failure_cause'];
                delete query['other'];
                
                // 提交
                common.ajax('POST', '/feedback/maintain-apply', {
                    'data': query
                }, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        var path = '?id=' + url.d_id;
                        
                        location.href = 'error-report-success.html' + path;
                    } else {
                        $.alert('很抱歉,故障上报失败,失败原因:' + rsp.data.message, '添加失败', function () {
                            status = true;
                        });
                    }
                })
            }
        });

        var pings = env.pings;
        pings();
    });

    $.init();
});
