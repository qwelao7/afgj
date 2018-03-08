require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#error-evaluation", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        common.img();

        function loadData() {
            common.ajax('GET', '/feedback/view-comment-result', {
                'id': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);

                    renderStar(data.comment.rate_star);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";
                    container.append(template);
                }
            })
        }

        function renderStar(num) {
            var key = parseInt(num) - 1;

            $('.rate-item').map(function (index, item) {
                if (index <= key) {
                    $(item).replaceWith('<i class="iconfont icon-xx1-hll open-panel rate-item" style="color: #efb336"></i>');
                } else {
                    $(item).replaceWith('<i class="iconfont icon-xx2-hll open-panel rate-item"></i>');
                }
            });
        }

        loadData();
        
        var pings = env.pings;pings();
    });

    $.init();
});