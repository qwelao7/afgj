require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#circle-blocklist", function (e, id, page) {
        var url = common.getRequest();
        var tpl = $('#tpl').html(),
            bbsId = url.id,
            container = $('#container');
        //加载数据
        function loadData(){
            common.ajax('GET','/forum/block-or-set',{bbsId:bbsId,type:1},true,function(rsg){
                if (rsg.data.code == 0) {
                    var data = rsg.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                }else if(rsg.data.code == 1){
                   var html = '<h2 class="center">暂无数据！</h2>';
                       container.append(html);
                }
            });
        }
        //移除黑名单
        $(document).on('click','.remove',function () {
            var self = $(this),
                account_id = self.data('id'),
                role = self.data('role');
            bbsId = self.data('bbsid');
            common.ajax('GET','/forum/remove',{
                'bbsId': bbsId,
                'account_id': account_id,
                'user_role': role,
                'type': 1
            },true,function(rsg){
                if (rsg.data.code == 0) {
                    $.alert('移出成功!', function() {
                        window.location.href = 'circle-blocklist.html?id='+bbsId;
                    });
                }else{
                    $.alert('很抱歉,移出失败,请重试!');
                }
            });
        });

        $(document).on('click','#back',function(){
            window.location.href = 'circle-detail.html?id='+bbsId;
        });
        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
