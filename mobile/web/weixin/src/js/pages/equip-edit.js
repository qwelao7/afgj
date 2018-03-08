require('../../css/style.css');
require('../../css/index.css');
require('../lib/awesomplete.min.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-edit", function (e, id, page) {

        var url = common.getRequest();
        /**自定义模板**/
        common.img();
        /**定义变量**/
        var container = $('#container'),
            tpl = $('#tpl').html(),
            items = $('#items').html(),
            imgArr = '',
            data = {},
            state = true,
            href = window.location.href;

        var path,
            kv_key,
            dataIds = [],
            dataNames = [],
            awesomplete,
            brandId;

        /**加载数据**/
        function loadData(){
            common.ajax('GET','/facilities/detail', {'id':url.id},false,function(rsp){
                if(rsp.data.code == 0){
                    var data = rsp.data.info,
                       html =  juicer(tpl,data);
                    container.append(html);
                    kv_key = data.kv_key;
                    brandId = data.brand;

                    //加载品牌类型
                    loadType();

                    imgArr = data.bill_pics;
                    pickerBuy(data.buy_date);
                    pickerGuarantee(data.guarantee_time);
                }else {
                    $.alert('很抱歉,服务器失去连接,请稍后...');
                }
            });
        }

        /** 加载类型 **/
        function loadType() {
            common.ajax('GET', '/facilities/equipment-list', {'type_id': kv_key}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    dataIds = rsp.data.info.id;
                    dataNames = rsp.data.info.name;

                    renderAwe(rsp.data.info);
                }
            })
        }

        /** 渲染自动补全 **/
        function renderAwe(data) {
            var input = document.getElementById("brand");
            awesomplete = new Awesomplete(input);

            var htm = juicer(items, data);
            $('div.awesomplete ul').prop('hidden', true).append(htm);

            awesomplete.list = data.name;
            awesomplete.minChars = 1;
        }

        /**
         * 购买时间
         */
        function pickerBuy(value) {
            $("#buy_date").calendar({
                value: [value]
            });
        }
        /**
         * 保修日期
         */
        function pickerGuarantee(value){
            $("#guarantee_time").calendar({
                value: [value]
            });
        }

        //获取微信配置
        function getConfig() {
            common.ajax('POST', '/wechat/config', {href: href}, true, function (rsp) {
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

        /** 点击上传图片 **/
        $(page).on('click', '.equip-imgs', function () {
            var self = $(this);
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
                                    var data = rsp.data.info,
                                        template = "<div class='box'>" +
                                            "<img src='" + data + "' style='width: 5rem;height: 5rem'>" +
                                            "<i class='iconfont icon-cancel delete' style='position: absolute;left: 4.4rem;top:-.8rem;color: red;z-index: 2;'></i>" +
                                            "</div>";
                                    self.hide();
                                    self.parent().prepend(template);
                                    imgArr = data.replace(common.QiniuDamain,'');
                                } else {
                                    $.alert('图片上传失败,请重试!');
                                }
                            })
                        }
                    });
                }
            });
        });

        /** 删除图片 **/
        $(page).on('click', '.delete', function () {
            imgArr = '';
            $('.box').remove();
            $('.equip-imgs').show();
        });

        /** 获取品牌id **/
        function setBrandId() {
            var result = $.trim($('#brand').val()),
                index = dataNames.indexOf(result);

            if (index == -1) {
                return 0
            } else {
                brandId = dataIds[index];
                return brandId;
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

            if(!state) return;
            state = false;

            data.brand = setBrandId();
            data.brand_name = $('#brand').val();
            data.model = $('#model').val();
            data.price = $('#price').val();
            data.buy_date = $('#buy_date').val();
            data.shop = $('#shop').val();
            data.guarantee_time = $('#guarantee_time').val();
            data.bill_pics = imgArr;
            data.equipment_type = kv_key;

            tips(data.guarantee_time, '请选择保修到期日', self, params);
            tips(data.shop, '请填写购买商店', self, params);
            tips(data.buy_date, '请选择购买时间', self, params);
            tips(data.price, '请填写设备价格', self, params);
            tips(data.model, '请填写设备类型', self, params);
            tips(data.brand_name, '请填写设备品牌', self, params);

            if (params.arr.indexOf('false') == -1) {
                var buydate = $('#buy_date').val().replace(/\-/g, '/');
                var guaranteedate = $('#guarantee_time').val().replace(/\-/g, '/');
                buydate = new Date(buydate);
                guaranteedate = new Date(guaranteedate);
                if(guaranteedate <= buydate) {
                    state = true;
                    $.alert('很抱歉,保修日期不能小于购买日期!');
                    return;
                }
                
                common.ajax('POST', '/facilities/update', {'id':url.id,'data': data}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('您的设施详情已修改成功!', function () {
                            window.location.href = 'equip-detail.html?id=' + url.id + '&address=' + url.address;
                        });
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
        $(page).on('click', '#back', function() {
            path = '?id=' + url.id + '&address=' + url.address;

            window.location.href = 'equip-detail.html' + path;
        });

        loadData();
        getConfig();
        var pings = env.pings;pings();
    });

    $.init();
});
