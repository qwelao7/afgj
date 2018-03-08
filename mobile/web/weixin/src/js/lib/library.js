var common = require('../lib/common.js');

$(function () {
    var content = $('#content'),
        popup = $('#popup'),
        classify = $('#classify').html(),
        tpl = $('#tpl').html(),
        tabBottom = $('.buttons-tab');

    var url = common.getRequest(),
        searchText = window.localStorage.getItem('library_search_text'),
        href = window.location.href,
        category = url.category,
        sort = ['1', '2', '3'],
        keywords = url.q,
        showSort, // 1-评价 2-借阅数 2-距离
        issearch,
        params = {}; 

    var loading = false,
        num = 2,
        nums;

    var lat,
        long;

    /**
     * 点击tag标签
     **/
    $(document).on('click', '#tag', function () {
        return false;

        $('#popup').css('display', 'block');
        $('#modal').toggleClass('modal-overlay-visible');
    });
    $(document).on('click', '#modal', function () {
        return false;

        hideModel();
    });

    /**
     * 选择图书类型
     */
    $(document).on('click', '.modal-p-list', function() {
        var _this = $(this),
            text = $.trim(_this.text());
        category = _this.data('id');

        //当前选中
        $('.modal-p-list').removeClass('font-green');
        _this.addClass('font-green');

        //赋值内容
        $('.title-search-span').find('span').html(text);
        hideModel();
    });

    /***
     * 搜索图书
     */
    $(document).on('click', '#search', function() {
        searchHandler();
    });

    /**
     * 切换tab页
     */
    $(document).on('click', '.tab-link', function() {
        var self = $(this),
            key = self.index();
        issearch = tabBottom.data('issearch');

        if (!issearch) {
            window.location.href = 'library-book-list.html?id=' + url.id + '&type=' + key;
        } else {
            $('.tab-link').removeClass('active');
            self.addClass('active');
            num = 2;
            searchAll();
        }
    });

    /**
     * 无限滚动
     */
    $(document).on('infinite', '.infinite-scroll', function () {
        nums = tabBottom.data('nums');
        issearch = tabBottom.data('issearch');
        showSort = $('.tab-link.active').index();

        if (!issearch) {
            url.id && scrollerLocal();
            !url.id && scrollerAll();
        } else {
            url.id && scrollerLocalSearch();
            !url.id && scrollerAllSearch();
        }
    });
    
    /**
     * 所有书架搜索
     */
    function searchAll() {
        showSort = $('.tab-link.active').index();
        $('.infinite-scroll-preloader').show();
        $('#init').addClass('hide');
        $('#container').removeClass('hide');

        common.ajax('GET', '/library/search-list', {
            'book_name': keywords,
            'book_type': category,
            'sort': sort[showSort],
            'longitude': long,
            'latitude': lat
        }, true, function(rsp) {
            content.empty();
            if (rsp.data.code == 0) {
                var data = rsp.data.info;

                if (data.list.length < 1) {
                    renderTips();return false;
                }

                data.classify = sort[showSort];
                var html = juicer(tpl, data);
                content.append(html);

                //参数初始
                nums = data.pagination.pageCount;
                if (nums == 1) {
                    // 删除加载提示符
                    $('.infinite-scroll-preloader').hide();
                }
                tabBottom[0].setAttribute('data-nums', nums);
                num = 2;
                loading = false;
            } else {
                renderTips();
            }
        })
    }

    /**
     * 未搜索时的滚动 (本书架)
     */
    function scrollerLocal() {
        if (loading) return;
        loading = true;

        if (num > nums) {
            // 删除加载提示符
            $('.infinite-scroll-preloader').hide();
            return;
        }

        common.ajax('GET', '/library/book-list', {
            'library_id': url.id,
            'sort': sort[showSort],
            'page': num
        }, true, function (rsp) {
            if (rsp.data.code == 0) {
                loading = false;
                var data = rsp.data.info;

                data.classify = sort[showSort];
                var htm = juicer(tpl, data);

                content.append(htm);
                num++;
            }
        });
        $.refreshScroller();
    }

    /**
     * 搜索时 所有书架 列表
     */
    function scrollerAllSearch() {
        if (loading) return;
        loading = true;
        if (num > nums) {
            // 删除加载提示符
            $('.infinite-scroll-preloader').hide();
            return;
        }

        common.ajax('GET', '/library/search-list', {
            'book_name': keywords,
            'book_type': category,
            'sort': sort[showSort],
            'longitude': long,
            'latitude': lat,
            'page': num
        }, true, function (rsp) {
            if (rsp.data.code == 0) {
                loading = false;
                var data = rsp.data.info;

                data.classify = sort[showSort];
                var htm = juicer(tpl, data);

                content.append(htm);
                num++;
            }
        });
        $.refreshScroller();
    }

    /**
     * render tips
     */
    function renderTips() {
        var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无书本信息!</h3>";
        content.append(template);
        $('.infinite-scroll-preloader').hide();
    }

    /** 隐藏弹出层 **/
    function hideModel() {
        $('#popup').css('display', 'none');
        $('#modal').toggleClass('modal-overlay-visible');
        $('.actions-modal').addClass('modal-out');
        setTimeout(function() {
            $('.actions-modal').remove();
        }, 200)
    }

    /**
     * 搜索事件
     */
    function searchHandler() {
        var _this = $('input[name=search]');
        keywords = $.trim(_this.val());

        if (keywords == '') {
            $.alert('请输入您要搜索的书籍名!', '搜索失败');
            return false;
        }

        //保存搜索历史
        common.saveStorageLimit('library_search_log', 10, keywords);
        
        //保存当前搜索的图书分类和关键词
        params.category = category;
        params.keywords = keywords;
        localStorage.setItem('curBookSearch', JSON.stringify(params));

        $('.buttons-tab')[0].setAttribute('data-issearch', true);
        searchAll();
    }

    /** 获取微信配置 **/
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
                        'getLocation'
                    ]
                });
                wx.ready(function() {
                    getLocation();
                })
            } else {
                $.alert('获取配置信息失败!');
            }
        })
    }

    /**
     * 调用地址接口
     */
    function getLocation() {
        wx.getLocation({
            type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
            success: function (res) {
                lat = res.latitude; // 纬度，浮点数，范围为90 ~ -90
                long = res.longitude; // 经度，浮点数，范围为180 ~ -180。

                !url.id && searchHandler();
            },
            error: function(error) {
                $.alert('很抱歉,当前无法获取您的位置信息,请重试!', '获取数据失败', function() {
                    location.reload();
                })
            }
        });
    }

    /**
     * 图书类别
     */
    function loadData() {
        common.ajax('GET', '/library/guess-you-search', {}, true, function(rsp) {
            if (rsp.data.code == 0) {
                var data = rsp.data.info;
                var htm = juicer(classify, data);
                popup.append(htm);

                if (category == 0) {
                    $('#curCategory').text('所有图书');
                } else {
                    $('#curCategory').text(data['book_type'][category]);
                }
            }
        });
    }

    getConfig();
    !url.id && loadData();
});