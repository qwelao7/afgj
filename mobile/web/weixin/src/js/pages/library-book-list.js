require('../../css/style.css');
require('../../css/index.css');
require('../lib/library.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-book-list", function (e, id, page) {
        var url = common.getRequest();

        common.img();

        var content = $('#content'),
            popup = $('#popup'),
            classify = $('#classify').html(),
            tpl = $('#tpl').html(),
            tabBottom = $('.buttons-tab');

        var sortArr = ['1', '2', '3'],
            nums,
            sort,
            issearch;
        sort = sortArr[url.type];

        function loadData() {
            common.ajax('GET', '/library/book-list', {'library_id': url.id, 'sort': sort}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data.classify = parseInt(url.type) + 1; //图书排序 [1-评价 2-借阅 3-距离]

                    var html = juicer(tpl, data),
                        htm = juicer(classify, data);

                    content.append(html);
                    popup.append(htm);

                    if (url.type) {
                        $('.tab-link').eq(url.type).addClass('active');
                    }

                    nums = data.pagination.pageCount;
                    tabBottom.data('nums', nums);

                    if (nums == 1) {
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').hide();
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无书本信息!</h3>";
                    content.append(template);
                }
            })
        }
        
        $(document).on('click', '.book-list', function() {
            var self = $(this),
                id = self.data('id');

            window.location.href = 'library-book-detail.html?id=' + id + '&ref=booklist&library=' + url.id + '&type=' + url.type;
        });
        
        $(document).on('click', '#back', function() {
            window.location.href = 'library-list.html';
        });

        /** 从书架搜索 **/
        $(document).on('focus', 'input[name=search]', function() {
            location.href = 'library-book-search.html';
        });

        loadData();
        
        var pings = env.pings;pings();
    });

    $.init();
});
