require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-book-detail", function (e, id, page) {
        var tpl = $('#tpl').html(),
            navButtons = $('#navButtons').html(),
            content = $('#content');

        var url = common.getRequest();
        var path;

        var config = {
            autoHeight: true,
            visiblilityFullfit: true,
            autoplayDisableOnInteraction: false,
            pagination: '.swiper-pagination',
            paginationClickable: true,
            loop: true
        };

        var params = localStorage.getItem('curBookSearch');
        params = JSON.parse(params);

        common.img();
        
        /**
         * 点击tag标签
         **/
        $(document).on('click', '#tag', function () {
            $('#popup').css('display', 'block');
            $('#modal').toggleClass('modal-overlay-visible');
        });
        $(document).on('click', '#modal', function () {
            $('#popup').css('display', 'none');
            $('#modal').toggleClass('modal-overlay-visible');
            $('.actions-modal').addClass('modal-out');
            setTimeout(function() {
                $('.actions-modal').remove();
            }, 200)
        });

        /** jucier 自定义模板函数 **/
        var time = function (data) {
            data = data.replace(/\-/g, '/');
            var date = new Date(data),
                M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-',
                D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ',
                h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':',
                m = date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes();
            return M + D + h + m;
        };
        juicer.register('time', time);

        function loadData() {
            common.ajax('GET', '/library/detail', {id: url.id}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data),
                        htm = juicer(navButtons, data);

                    content.append(html);
                    content.after(htm);

                    $('.swiper-container').css({
                        paddingBottom: 0
                    });

                    $(".swiper-container").swiper(config);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无图书详情!</h3>";
                    content.append(template);
                }
            })
        }
        
        $(document).on('click', '#back', function() {
            if (url.ref == 'libraryindex') {
                location.href = 'library-index.html';
            } else if (url.ref == 'search') {
                location.href = 'library-search.html?category=' + params.category + '&q=' + params.keywords;
            } else {
                var type = url.type ? url.type : 0;
                location.href = 'library-book-list.html?id=' + url.library + '&type=' + type;
            }
        });

        $(document).on('click', '#commentBtn', function() {
            if (url.ref == 'libraryindex') {
                path = '&ref=libraryindex'
            } else if (url.ref == 'booklist') {
                path = '&ref=booklist&library=' + url.library + '&type=' + url.type;
            } else if (url.ref == 'search') {
                path = '&ref=search';
            }
            window.location.href = 'library-book-comment.html?id=' + url.id + path;
        });

        $(document).on('click', '.open-report', function() {
            $('#popup').hide();
            $('.modal-overlay').toggleClass('modal-overlay-visible');
            $.popup('.popup-report');
        });

        /** popup **/
        var status = true,
            arr = ['图书丢失', '图书损坏', '其他'],
            error;

        $(document).on('click', '#picker', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var buttons1 = [
                {
                    text: '图书丢失',
                    bold: true,
                    color: 'danger',
                    onClick: function () {
                        error = $(this)[0].text;
                        $('#errType').val(error);
                        error = arr.indexOf(error) + 1;
                    }
                },
                {
                    text: '图书损坏',
                    bold: true,
                    color: 'danger',
                    onClick: function () {
                        error = $(this)[0].text;
                        $('#errType').val(error);
                        error = arr.indexOf(error) + 1;
                    }
                },
                {
                    text: '其他',
                    onClick: function () {
                        error = $(this)[0].text;
                        $('#errType').val(error);
                        error = arr.indexOf(error) + 1;
                    }
                }
            ];
            var buttons2 = [
                {
                    text: '取消',
                    onClick: function () {
                        error = '';
                        $('#errType').val('');
                    }
                }
            ];

            var groups = [buttons1, buttons2];
            $.actions(groups);
        });

        $(document).on('click', '#submit', function () {
            if (!status) return;
            status = false;

            if (!error || error == undefined || error == '') {
                $.alert('很抱歉,请选择图书异常类型', '提交失败', function () {
                    status = true;
                });
                return;
            }

            var message = $.trim($('#textarea').val());

            if (!message || message == undefined || message == '') {
                $.alert('很抱歉,请填写异常描述', '提交失败', function() {
                    status = true;
                });
                return;
            }

            common.ajax('POST', '/library/borrow-error', {'id': url.id, 'type': error, 'comment': message}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('图书异常上报成功', '提交成功', function() {
                        $.closeModal('.popup-report');
                        status = true;
                    })
                } else {
                    $.alert('很抱歉,提交失败,请重试!', '提交失败', function() {
                        status = true;
                    })
                }
            })
        });

        $(document).on('click', '#backPopup', function() {
            $.closeModal('.popup-report');
        });

        $(document).on('click', '.commerce-list', function() {
            var commerceHref = $(this).data('href');

            window.location.href = commerceHref;
        });

        $(document).on('click', '#how-to-return', function() {
            //如何归还
            location.href = 'library-book-guide.html?id=' + url.id + '&type=0';
        });

        $(document).on('click', '#how-to-borrow', function() {
            //如何归还
            location.href = 'library-book-guide.html?id=' + url.id + '&type=1';
        });
        
        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
