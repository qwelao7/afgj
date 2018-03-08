require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#library-book-add', function (e, id, page) {
        var libraries = [],
            ids = [],
            pick = $('#picker'),
            status = true,
            href = window.location.href;

        var url = common.getRequest(),
            params = {};

        function loadData() {
            common.ajax('GET', '/library/bookshelf-list', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    if (!common.isEmptyObject(data)) {
                        for(var i in data) {
                            libraries.push(data[i]['library_name']);
                            ids.push(data[i]['id']);
                        }

                        pick.val(libraries[0]);
                        pick[0].setAttribute('data-library_id', ids[0]);
                        listPicker();
                        getStorage();
                    }
                }
            })
        }

        function listPicker() {
            pick.picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">选择书架</h1>\
                                </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: libraries
                    }
                ],
                onClose: function() {
                    var value = $.trim(pick.val()),
                        index = libraries.indexOf(value);

                    pick[0].setAttribute('data-library_id', ids[index]);
                }
            });
        }

        $(document).on('click', '#submit', function() {
            if (!status) return false;
            status = false;

            var libraryId = pick.data('library_id'),
                donateNum = $('input[type=tel]').val();

            scanQRCode(libraryId, donateNum);
        });

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
                            'scanQRCode'
                        ]
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        /**
         * 调用微信扫码接口 (返回isbn)
         */
        function scanQRCode(library_id, donate) {
            wx.scanQRCode({
                needResult: 1,
                scanType: ["qrCode","barCode"],
                success: function(res) {
                    var result = res.resultStr;
                    submit(library_id, donate, result)
                },
                error: function() {
                    $.alert('很抱歉, 扫码出现故障,请重试!');
                    status = true;
                }
            })
        }

        /**
         * 调用微信扫码接口 (跳转页面)
         */
        function scanQr () {
            wx.scanQRCode({
                needResult: 0,
                scanType: ["qrCode","barCode"],
                success: function(res) {
                },
                error: function() {
                    $.alert('很抱歉, 扫码出现故障,请重试!');
                    status = true;
                }
            })
        }

        /**
         * 保存localstorage
         */
        function saveStorage(str) {
            localStorage.setItem('donate', str);
        }

        /**
         * 提交图书上架
         */
        function submit(library_id, donate, isbn) {
            common.ajax('GET', '/library/put-away', {
                'library_id': library_id,
                'code': donate,
                'isbn': isbn,
                'qr_code': url.qr_code
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('图书上传成功!', '上传成功', function() {
                        status = true;
                        $('input[type=tel]').val('');
                        var library = $('#picker').val();
                        setStorage(donate, library, library_id);

                        /**
                         * 重新扫码
                         */
                        tryAgain();
                    })
                } else {
                    $.alert('很抱歉,图书上传失败,' + rsp.data.message, '上传失败', function() {
                        status = true;
                    })
                }
            })
        }

        function setStorage(donate, library, id) {
            params['donate'] = donate;
            params['library'] = library;
            params['id'] = id;
            params = JSON.stringify(params);
            localStorage.setItem('uploadBook', params);
        }

        function getStorage() {
            var str = localStorage.getItem('uploadBook');
            if (str == '' || str == undefined || str == null) {
                return false;
            } else {
                str = JSON.parse(str);
                $('input[type=tel]').val(str['donate']);
                $('#picker').val(str['library']);
                $('#picker')[0].setAttribute('data-library_id', str['id']);
            }
        }

        function tryAgain() {
            $.modal({
                title: '是否继续上传',
                text: '是否继续上传图书',
                buttons: [
                    {
                        text: '知道了'
                    },
                    {
                        text: '继续上传',
                        bold: true,
                        onClick: function () {
                            scanQr()
                        }
                    }]
            });
        }

        $(document).on('click', '#addLibrary', function() {
            location.href = 'library-add.html?qr_code=' + url.qr_code;
        });

        loadData();
        getConfig();

        var pings = env.pings;pings();
    });
    $.init();
});