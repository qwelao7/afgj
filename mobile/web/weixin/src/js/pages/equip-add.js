require('../../css/style.css');
require('../../css/index.css');
require('../lib/awesomplete.min.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-add", function (e, id, page) {
        var url = common.getRequest();
        /**
         * 定义数组
         * @type {string[]}
         */
        var info = {},
            type,
            num,
            typeName,
            typeNum,
            data = {},
            desc,
            addressId,
            state = true;

        var awesomplete,
            dataIds = [],
            dataNames = [],
            count = 1;

        var tpl = $('#tpl').html(),
            address = $('#address').html(),
            items = $('#items').html(),
            classifyList = $('#classifyList'),
            addressList = $('#addressList');

        /**
         * 加载地址
         */
        function loadAddress() {
            common.ajax('GET', '/hcho/auth-address', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    desc = rsp.data.info.name;
                    addressId = rsp.data.info.id;
                    var defaultnum = addressId.indexOf(url.id);
                    if (defaultnum == -1) {
                        defaultnum = 0;
                        num = addressId[0];
                    } else {
                        num = url.id;
                    }
                    info.num = desc[defaultnum];
                    var html = juicer(address, info);
                    addressList.prepend(html);
                    pickAddress();
                } else if (rsp.data.code == 110) {
                    $.modal({
                        title: '友情提示',
                        text: rsp.data.message + ', 请创建您的房产!',
                        buttons: [
                            {
                                text: '知道了',
                                onClick: function () {
                                    window.history.go(-1);
                                }
                            },
                            {
                                text: '立即前往',
                                bold: true,
                                onClick: function () {
                                    window.location.href = 'estate-manage.html';
                                }
                            }
                        ]
                    })
                } else {
                    $.alert(rsp.data.message);
                }
            });
        }

        /**
         * 选择地址
         */
        function pickAddress() {
            $("#picker1").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                   <button class="button button-link pull-right close-picker font-white">确定</button>\
                                   <h1 class="title font-white">请选择房产</h1>\
                                   </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: desc
                    }
                ],
                onClose: function () {
                    var str = $('#picker1').val();
                    str = $.trim(str);
                    var index = desc.indexOf(str);
                    num = addressId[index];
                }
            });
        }

        /**
         * 加载类型
         */
        function loadClassify() {
            common.ajax('GET', '/facilities/equipment-type', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    typeName = rsp.data.info.typeName;
                    typeNum = rsp.data.info.typeNum;
                    var defaulttype = typeNum.indexOf(url.type);
                    if (defaulttype == -1) {
                        type = typeNum[0];
                        defaulttype = 0;
                    } else {
                        type = url.type;
                    }
                    info.type = typeName[defaulttype];
                    var htm = juicer(tpl, info);
                    classifyList.prepend(htm);

                    //加载品牌列表
                    getBrandlist();
                    pickClassify();
                } else {
                    $.alert(rsp.data.message);
                }
            });
        }

        /**
         * 选择小区
         */
        function pickClassify() {
            $("#picker2").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                               <button class="button button-link pull-right close-picker font-white">确定</button>\
                               <h1 class="title font-white">请选择物品分类</h1>\
                               </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: typeName
                    }
                ],
                onClose: function () {
                    var str = $('#picker2').val();
                    str = $.trim(str);
                    var index = typeName.indexOf(str);
                    type = typeNum[index];

                    //重新加载品牌类型
                    getBrandlist();
                }
            });
        }

        /** 加载设备品牌列表 **/
        function getBrandlist() {
            //清空品牌填写内容
            $('#brand').val('');

            common.ajax('GET', '/facilities/equipment-list', {'type_id': type}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    dataIds = rsp.data.info.id;
                    dataNames = rsp.data.info.name;

                    count == 1 && aweInit();
                    renderAwe(rsp.data.info);

                    count++;
                }
            })
        }

        function aweInit() {
            var input = document.getElementById("brand");
            awesomplete = new Awesomplete(input);
        }

        /** 渲染自动补全 **/
        function renderAwe(data) {
            var tem = juicer(items, data);
            $('div.awesomplete ul').prop('hidden', true).empty().append(tem);

            awesomplete.list = data.name;
            awesomplete.minChars = 1;
        }

        /** 匹配brand_id **/
        function setBrandId() {
            var result = $.trim($('#brand').val()),
                index = dataNames.indexOf(result);

            if (index == -1) {
                return 0
            } else {
                return dataIds[index];
            }
        }

        /**
         * 提交信息
         */
        $(page).on('click', '#submit', function (event) {
            event.preventDefault();

            var self = $(this),
                params = {};
            params.arr = [];
            params.err = '';

            if (!state) return;
            state = false;

            data.address_id = num;
            data.equipment_type = type;
            data.brand = setBrandId();
            data.brand_name = $('#brand').val();
            data.model = $('#model').val();
            data.price = $('#price').val();
            data.buy_date = $('#buy_date').val();
            data.shop = $('#shop').val();
            data.guarantee_time = $('#guarantee_time').val();

            tips(data.guarantee_time, '请选择保修到期日', self, params);
            tips(data.shop, '请填写购买商店', self, params);
            tips(data.buy_date, '请选择购买时间', self, params);
            tips(data.price, '请填写设备价格', self, params);
            tips(data.model, '请填写设备类型', self, params);
            tips(data.brand_name, '请填写设备品牌', self, params);
            tips(data.address_id, '请填写你的房产', self, params);
            tips(data.equipment_type, '请选择工具类型', self, params);
            if (params.arr.indexOf('false') == -1) {
                var buydate = $('#buy_date').val().replace(/\-/g, '/');
                var guaranteedate = $('#guarantee_time').val().replace(/\-/g, '/');
                buydate = new Date(buydate);
                guaranteedate = new Date(guaranteedate);
                if (guaranteedate <= buydate) {
                    state = true;
                    $.alert('很抱歉,保修日期不能小于购买日期!');
                    return;
                }
                
                common.ajax('POST', '/facilities/create-by-user', {'data': data}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        window.location.href = 'equip-add-success.html?id=' + num;
                    } else {
                        state = true;
                        $.alert('您的提交失败,请重试!');
                    }
                });
            } else {
                params.arr = [];
                state = true;
                $.alert(params.err);
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
        $(page).on('click', '#back', function () {
            window.location.href = 'equip-list.html?id=' + url.id;
        });

        loadAddress();
        loadClassify();
        var pings = env.pings;
        pings();
    });

    $.init();
});
