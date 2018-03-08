require('../../css/style.css');
require('../../css/index.css');
require('../lib/qiniu.min.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#fault-feedback", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            content = $('#content');

        var contentArr = [],
            status = true;

        var validArr = [],
            transArr = [],
            key,
            imgUrl,
            name;

        function loadData() {
            common.ajax('GET', '/feedback/community-auth', {
                'id': url.id,
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    renderDetail();
                } else {
                    name = rsp.data.info.name;

                    $.modal({
                        title: '温馨提示',
                        text: '您暂未拥有该小区房产,请先添加房产!',
                        buttons: [
                            {
                                text: '知道了',
                                onClick: function () {
                                    window.history.go(-1);
                                }
                            },
                            {
                                text: '前往认证',
                                bold: true,
                                onClick: function () {
                                    localStorage.setItem('community', '{"id":' +  url.id +   ',"name":"' + name + '"}');

                                    window.location.href = 'estate-add.html?type=0';
                                }
                            }
                        ]
                    });
                }
            });
        }

        function renderDetail() {
            common.ajax('GET', '/feedback/community-feedback', {
                'id': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data,
                        html = juicer(tpl, data);

                    content.append(html);

                    //initParams
                    initParams(data.info);

                    //renderPicker
                    renderPicker(data.info);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: .5rem;'>暂无数据!</h3>";
                    content.append(template);
                }
            })
        }

        function initParams(data) {
            for (var i in data) {
                if (data[i]['type'] != 'content') {
                    validArr.push(i);
                    transArr.push(data[i]['label']);
                }
            }
        }

        function uploader() {
            var uploader = Qiniu.uploader({
                runtimes: 'html5,flash,html4',      // 上传模式，依次退化
                browse_button: 'pickfiles',         // 上传选择的点选按钮，必需
                uptoken_url: common.WEBSITE_API + '/wechat/upload-token', // uptoken是上传凭证，由其他程序生成
                get_new_uptoken: false,             // 设置上传文件的时候是否每次都重新获取新的uptoken
                unique_names: false,              // 默认false，key为文件名。若开启该选项，JS-SDK会为每个文件自动生成key（文件名）
                save_key: true,                  // 默认false。若在服务端生成uptoken的上传策略中指定了sava_key，则开启，SDK在前端将不对key进行任何处理
                domain: common.QiniuDamain,     // bucket域名，下载资源时用到，必需
                container: 'container',             // 上传区域DOM ID，默认是browser_button的父元素
                max_file_size: '100mb',             // 最大文件体积限制
                flash_swf_url: 'path/of/plupload/Moxie.swf',  //引入flash，相对路径
                max_retries: 3,                     // 上传失败最大重试次数
                dragdrop: false,                     // 开启可拖曳上传
                drop_element: 'container',          // 拖曳上传区域元素的ID，拖曳文件或文件夹后可触发上传
                chunk_size: '4mb',                  // 分块上传时，每块的体积
                auto_start: true,                   // 选择文件后自动上传，若关闭需要自己绑定事件触发上传
                init: {
                    'FilesAdded': function (up, files) {
                        // 文件添加进队列后，处理相关的事情
                        plupload.each(files, function (file) {
                        });
                    },
                    'BeforeUpload': function (up, file) {
                        // 每个文件上传前，处理相关的事情
                        $.showPreloader('文件上传中...');
                    },
                    'UploadProgress': function (up, file) {
                        // 每个文件上传时，处理相关的事情
                    },
                    'FileUploaded': function (up, file, info) {
                        // 每个文件上传成功后，处理相关的事情
                        // 其中info是文件上传成功后，服务端返回的json
                        // 查看简单反馈
                        $.hidePreloader();

                        var domain = up.getOption('domain');
                        var res = JSON.parse(info);
                        var sourceLink = domain + encodeURIComponent(res.key); //获取上传成功后的文件的Url

                        var brother = $('#container').next();

                        if (file.type.indexOf('image') != -1) {
                            renderPics(sourceLink, brother);
                        } else {
                            renderDocs(sourceLink, res.key, brother);
                        }
                    },
                    'Error': function (up, err, errTip) {
                        //上传出错时，处理相关的事情
                        alert('上传失败');
                    },
                    'UploadComplete': function () {
                        //队列文件处理完毕后，处理相关的事情
                    }
                }
            });

            setTimeout(function() {
                $('#hide-span').css('opacity', 0);
                $('.icon-camera').removeClass('hide');
                $('.moxie-shim').find('input').css('z-index', 30);
            }, 100);
        }

        function renderPicker(data) {
            for (var i in data) {
                if (data[i]['type'] == 'time') {
                    $('#picker_' + i).datetimePicker({
                        onClose: function () {

                        }
                    });
                }
            }
        }

        function renderPics(link, brother) {
            var template = "<div class='col-33 box' style='position: relative;padding: .3rem 0;'>" +
                "<img src='" + link + "' style='width: 4rem;height: auto;max-height: 4rem;'>" +
                "<i class='iconfont icon-cancel delete' data-type='img' data-link='" + link + "' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                "</div>";

            $('#rows').append(template);
            link = link.replace(common.QiniuDamain, '');
            contentArr.push(link);

            brother.val(contentArr.join(','));
        }

        function renderDocs(link, title, brother) {
            var template = "<div class='col-33 box' style='position: relative;padding: .3rem 0;'>" +
                "<div style='width: 4rem;margin: 0 auto;'><i class='icon iconfont icon-news' style='font-size: 1.6rem;display: block;margin: 0 auto;height: auto;'></i></div>" +
                "<p style='color: #3d4145;font-size: .6rem;text-align: center;' class='ellipsis-full'>" + title + "</p>" +
                "<i class='iconfont icon-cancel delete' data-type='doc' data-link='" + link + "'style='position: absolute;right: .5rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                "</div>";

            $('#rows').append(template);
            link = link.replace(common.QiniuDamain, '');
            contentArr.push(link);

            brother.val(contentArr.join(','));
        }

        function validate(params) {
            for (var i in params) {
                if (params[i] == '' && validArr.indexOf(i) != -1) {
                    key = validArr.indexOf(i);
                    $.alert('很抱歉,' + transArr[key] + '不能为空!', '验证失败', function () {
                        status = true;
                    });
                    return false;
                }
            }
            return true;
        }

        /**
         * 获取微信配置
         */
        function getConfig() {
            common.ajax('POST', '/wechat/config', {href: window.location.href}, true, function (rsp) {
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

        $(document).on('click', '.icon-camera', function () {
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
                                imgUrl = rsp.data.info;

                                var brother = $('#container').next();

                                renderPics(imgUrl, brother);
                            })
                        }
                    });
                }
            });
        });

        $('#back').on('click', function() {
            if (url.ref != undefined) {
                location.href = 'square-tab-index.html?id=' + url.id + '&ref=' + url.ref;
            } else {
                location.href = 'square-tab-index.html?id=' + url.id;
            }
        });

        $('.delete').live('click', function () {
            var self = $(this),
                parent = self.parents('.box'),
                type = self.data('type'),
                link = self.data('link'),
                index;

            var brother = $('#container').next();

            index = contentArr.indexOf(link);
            contentArr.splice(index, 1);
            brother.val(contentArr.join(','));

            parent.remove();
        });

        $('#submit').on('click', function () {
            if (!status) return false;
            status = false;

            var query = common.formToJson($('form').serialize());
            query = JSON.parse(decodeURIComponent(query));

            if (validate(query)) {
                query = JSON.stringify(query);

                common.ajax('POST', '/feedback/community-apply', {
                    'community_id': url.id,
                    'content': query,
                    'work_id': url.work_id
                }, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('问题反馈成功,感谢您对' + rsp.data.info + '、回来啦社区的支持!', '反馈成功', function() {
                            if (url.ref && url.ref != '') {
                                location.href = 'square-tab-index.html?id=' + url.id + '&ref=' + url.ref;
                            } else {
                                location.href = 'square-tab-index.html?id=' + url.id;
                            }
                        });
                    } else {
                        $.alert('很抱歉,问题反馈失败,失败原因:' + rsp.data.message, '反馈失败', function() {
                            status = true;
                        })
                    }
                })
            }
        });

        $('#mine').on('click', function() {
            var path = '?id=' + url.id + '&work_id='+url.work_id;
            path = (url.ref != undefined) ? path + '&ref=' + url.ref : path;

            location.href = 'fault-reply-list.html' + path;
        });

        loadData();
        getConfig();

        var pings = env.pings;
        pings();
    });

    $.init();
});