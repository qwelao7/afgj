require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#brand-list", function (e, id, page) {

        function loadData() {
            var container = $('#container'),
                tpl = $('#tpl').html(),
                data = {};
            data.param = '';

            var u = navigator.userAgent;
            if (u.indexOf('Android') > -1 || u.indexOf('Linux') > -1) {//安卓手机
                data.param = "http://dl.m.cc.youku.com/android/phone/Youku_Android_pcdaoliu.apk";
            } else if (u.indexOf('iPhone') > -1) {
                // data.param = "http://mp.weixin.qq.com/mp/redirect?url=https://itunes.apple.com/cn/app/id336141475?l=cn&mt=8&spm=a2hmb.20008760.m_221044.5~5~5~5~5~5~P~A!2.OqHSgm";
                data.param = "http://a.app.qq.com/o/simple.jsp?pkgname=com.youku.phone";
            };

            var html = juicer(tpl, data);
            container.append(html);
        }

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});