require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-book-search", function (e, id, page) {
        var classify = $('#classify').html(),
            items = $('#items').html(),
            tpl = $('#tpl').html(),
            popup = $('#popup'),
            init = $('#init'),
            container = $('#container');

        var category = 0; //默认所有图书

        var storage = window.localStorage.getItem('library_search_log');
        storage = (storage == null) ? [] : storage.split(',');
        
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

        //加载数据
        function loadData() {
            common.ajax('GET', '/library/guess-you-search', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data['history'] = storage;
                    var html = juicer(items, data),
                        htm = juicer(classify, data);
                    popup.append(htm);
                    init.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无具体信息!</h3>";
                    init.append(template);
                }
            });
        }

        //清楚搜索历史
        $(document).on('click', '#clean', function() {
            var _this = $(this);
            window.localStorage.removeItem('library_search_log');
            $('#historyList').empty();
            _this.hide();
        });

        //返回
        $(document).on('click', '#back', function() {
            location.href = 'library-list.html';
        });

        //跳转搜索具体
        $(document).on('click','#to-search', function () {
            goSearch();
        });

        //跳转搜索
        $(document).on('click', '.search-word', function() {
            var _this = $(this),
                text = $.trim(_this.text());
            $('input[name=search]').val(text);
            
            goSearch();
        });

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
         * 跳转搜索
         */
        function goSearch() {
            var val = $.trim($('input[name=search]').val());
            if (val == '') {
                $.alert('请输入您要搜索的书籍名!', '搜索失败');
            } else {
                location.href = 'library-search.html?category=' + category + '&q=' + val;
            }
        }

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
