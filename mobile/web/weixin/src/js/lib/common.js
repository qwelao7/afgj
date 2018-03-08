var util = new Object();
util = {
    /**
     * 获取url参数
     * **/
    getRequest: function () {
        var url = location.search;
        url = decodeURIComponent(url);
        url = this.escapeHtml(url);
        var theRequest = new Object();
        if (url.indexOf("?") != -1) {
            var str = url.substr(1),
                strs = str.split("&");
            for (var i = 0; i < strs.length; i++) {
                theRequest[strs[i].split("=")[0]] = (strs[i].split("=")[1]);
            }
        }
        return theRequest;
    },
    /**
     * 返回特定字符串第n次出现的位置
     * **/
    strIndexOf: function (str, cha, num) {
        var x = str.indexOf(cha);
        for (var i = 0; i < num; i++) {
            x = mystr.indexOf(cha, x + i);
        }
        return x;
    },
    /**
     * 日期转星期
     * **/
    dateFormatDay: function (date) {
        var weekArray = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];
        return weekArray[date.getDay()];
    },
    /**
     * 模板渲染
     * **/
    renderTpl: function (tpl, data) {
        var complied_tpl = juicer(tpl);
        var render = complied_tpl.render(data);
        return render;
    },
    /**
     * 验证合法
     * **/
    /** 账号验证规则: 字母、数字、下划线组成，字母开头，4-16位 (type:1)**/
    /** 手机验证规则: 11位数字，以1开头(type:2)**/
    /** 验证电话号码：区号+号码，区号以0开头，3位或4位 (type:3)**/
    /** 验证邮箱 验证规则：例如xxxxx@xxx.com” (type:4)**/
    check: function (str, type) {
        switch (type) {
            case 1:
                var re = /(?!^\d+$)(?!^[a-zA-Z]+$)[0-9a-zA-Z]\w{3,15}/;
                break;
            case 2:
                var re = /^[1][3578][0-9]{9}$/;
                break;
            case 3:
                var re = /^((010|02\d{1}|0[3-9]\d{2})-)?\d{7,9}(-\d+)?$/;
                break;
            case 4:
                var re = /^(\w-*\.*)+@(\w-?)+(\.\w{2,})+$/;
                break;
        }
        var check = re.test(str);
        return check;
    },
    /**
     * 数组分组
     * **/
    group: function (by, arr) {
        if (arr == []) return arr;
        var result = [];
        arr.forEach(function (item) {
            if (!result[item[by] + '月']) {
                result[item[by] + '月'] = [];
            }
            result[item[by] + '月'].push(item);
        });
        return result;
    },
    /**
     * 跳转页面缓存本地存储(一次性)
     * **/
    saveStorage: function (data) {
        var storage = window.localStorage,
            skip = storage.getItem('skip');
        if (skip == null) {
            storage.setItem('skip', '0');
        }
        ;
        if (typeof data == "object") {
            storage.setItem('skip', JSON.stringify(data));
        } else {
            storage.setItem('skip', data);
        }
        return skip;
    },
    /**
     * 截取图片地址的域名
     * **/
    imgUrl: function (data) {
        var str = [];
        var result = data.map(function (item) {
            str = item.split("/");
            return item = str[str.length - 1];
        });
        return result;
    },
    /**
     * 将数组按某个元素的值分组
     * @param string by
     * @param array 需要分组的数组
     * @param string key 主键
     **/
    groupBy: function (by, arr, key) {
        if (arr == []) return arr;
        var result = [];
        if (!key) {
            arr.forEach(function (item) {
                if (!result[item[by]]) {
                    result[item[by]] = [];
                }
                result[item[by]].push(item);
            });
        } else {
            arr.forEach(function (item) {
                if (!result[item[by]]) {
                    result[item[by]] = [];
                }
                result[item[by]][item[key]] = item;
            })
        }
        return result;
    },
    /**
     * 日期转换 距离多少天
     * **/
    formatTime: function (the_time) {
        var nowTime = new Date(),
            theTime = new Date(the_time * 1000),
            dur = nowTime.getTime() - theTime.getTime();

        var Y = theTime.getFullYear() + '-',
            M = (theTime.getMonth() + 1 < 10 ? '0' + (theTime.getMonth() + 1) : theTime.getMonth() + 1) + '-',
            D = (theTime.getDate() < 10 ? '0' + theTime.getDate() : theTime.getDate()) + ' ',
            h = (theTime.getHours() < 10 ? '0' + theTime.getHours() : theTime.getHours()) + ':',
            m = theTime.getMinutes() < 10 ? '0' + theTime.getMinutes() : theTime.getMinutes(),
            s = ':' + (theTime.getSeconds() < 10 ? '0' + theTime.getSeconds() : theTime.getSeconds());
        if (dur < 0) {
            return M + D + h + m;
        } else {
            if (dur < 86400000) {
                return h + m;
            } else {
                if (dur < 604800000) {
                    return Math.floor(dur / 86400000) + '天前';
                } else {
                    if (dur < 2592000000) {
                        return Math.floor(dur / 604800000) + '周前';
                    } else {
                        return M + D + h + m;
                    }
                }
            }
        }
    },
    /**
     * 模板自定义函数  img
     * **/
    img: function () {
        var qiniu = this.QiniuDamain;
        var imgPath = function (data) {
            return qiniu + data;
        };
        juicer.register('imgPath', imgPath);
        return true;
    },
    /**
     * 自定义ajax
     * **/
    ajax: function (type, url, data, asnyc, success, error) {
        var _this = this;


        $.ajax({
            type: type,
            url: 'v2' + url,
            data: data,
            dataType: 'json',
            asnyc: asnyc,
            success: function (rsp) {
                if (rsp.ret == 405) {
                    window.location.href = rsp.data.info + '&referer=' + location.href;
                    return;
                }
                success(rsp);
            },
            error: function (xhr, errorType, error) {
                var str = 'text: ' + xhr.responseText + ' &responseUrl: ' + xhr.responseURL + ' &url: ' + url + ' &data: ' + JSON.stringify(data);

                $.ajax({
                    type: 'POST',
                    url: 'v2/error/weixin',
                    data: {'content': str},
                    success: function (rsp) {
                        if (location.host.indexOf('huilaila') != -1) {
                            location.href = 'http://mall.huilaila.net/';
                        }
                    }
                })
            }
        });
        return true;
    },
    /**
     * 判断是否为微信浏览器
     * **/
    isWeixin: function () {
        var ua = window.navigator.userAgent.toLowerCase();
        if (ua.match(/MicroMessenger/i) == 'micromessenger') {
            return true;
        } else {
            return false;
        }
    },


    /**
     * 设置cookie
     */
    setCookie: function (name, value) {
        var Days = 30;
        var exp = new Date();
        exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
        document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
    },

    /**
     * 读取cookie
     */
    getCookie: function (name) {
        var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
        return (arr = document.cookie.match(reg)) ? unescape(arr[2]) : null;
    },

    /**
     * 防止sql注入
     */
    AntiSqlValid: function (oField) {
        re = /select|update|delete|exec|count|'|"|=|;|>|<|%/i;
        if (re.test(oField.value)) {
            $.alert("请您不要在参数中输入特殊字符和SQL关键字！");
            oField.value = "";
            oField.className = "errInfo";
            oField.focus();
            return false;
        }
    },
    /**
     * 日期格式化为(年月日)
     * time 时间戳
     */
    formatDate: function (time) {
        var date;
        date = new Date(time * 1000);
        var year = date.getFullYear(),
            month = date.getMonth() + 1,
            day = date.getDate();
        return year + '-' + month + '-' + day;
    },
    dateFormat: function (time, fmt) {
        time = time.replace(/\-/g, '/');
        var date = new Date(time),
            o = {
                "M+": date.getMonth() + 1, //月份
                "d+": date.getDate(), //日
                "h+": date.getHours(), //小时
                "m+": date.getMinutes(), //分
                "s+": date.getSeconds(), //秒
                "q+": Math.floor((date.getMonth() + 3) / 3), //季度
                "S": date.getMilliseconds() //毫秒
            };
        if (/(y+)/.test(fmt)) {
            fmt = fmt.replace(RegExp.$1, (date.getFullYear() + "").substr(4 - RegExp.$1.length));
        }
        for (var k in o) {
            if (new RegExp("(" + k + ")").test(fmt)) {
                fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
            }
        }
        return fmt;
    },
    /**
     * 时间字符串转日期 (安卓、ios通用)
     */
    timeStringToDate: function (time) {
        time = time.replace(/\-/g, '/');
        return new Date(time);
    },
    /**
     * 转义符转字符串
     */
    escapeHtml: function (str) {
        var arrEntities = {'lt': '<', 'gt': '>', 'nbsp': ' ', 'amp': '&', 'quot': '"'};
        return str.replace(/&(lt|gt|nbsp|amp|quot);/ig, function (all, t) {
            return arrEntities[t];
        });
    },
    /**
     * 计算两点经纬度距离
     * @type {String}
     */
    getFlatternDistance: function (lat1, lng1, lat2, lng2) {
        var f = this.getRad((lat1 + lat2) / 2);
        var g = this.getRad((lat1 - lat2) / 2);
        var l = this.getRad((lng1 - lng2) / 2);

        var sg = Math.sin(g);
        var sl = Math.sin(l);
        var sf = Math.sin(f);

        var s, c, w, r, d, h1, h2, result;
        var a = 6378137.0;
        var fl = 1 / 298.257;

        sg = sg * sg;
        sl = sl * sl;
        sf = sf * sf;

        s = sg * (1 - sl) + (1 - sf) * sl;
        c = (1 - sg) * (1 - sl) + sf * sl;

        w = Math.atan(Math.sqrt(s / c));
        r = Math.sqrt(s * c) / w;
        d = 2 * w * a;
        h1 = (3 * r - 1) / 2 / c;
        h2 = (3 * r + 1) / 2 / s;

        result = d * (1 + fl * (h1 * sf * (1 - sg) - h2 * (1 - sf) * sg));
        return result.toFixed(0);
    },
    /**
     * getRad
     * @type {String}
     */
    getRad: function (d) {
        var PI = Math.PI;
        return d * PI / 180.0;
    },
    /**
     * 表单序列化后转json字符串
     * **/
    formToJson: function (data) {
        data = data.replace(/&/g, "\",\"");
        data = data.replace(/=/g, "\":\"");
        data = "{\"" + data + "\"}";
        return data;
    },
    /**
     * 判断对象是否为空
     * **/
    isEmptyObject: function (obj) {
        for (var key in obj) {
            return false;
        }
        return true;
    },
    /**
     * localstorage 存储 容量限制
     */
    saveStorageLimit: function (key, limit, value) {
        if (value == '' || value == undefined) return false;
        if (value instanceof Array == false && typeof value == 'string') {
            value = value.split(',');
        } else {
            return false;
        }
        var storage = window.localStorage.getItem(key);
        storage = (storage == null) ? [] : storage.split(',');
        storage = value.concat(storage);
        storage = this.unique(storage);

        storage = (storage.length > limit) ? storage.slice(0, 9) : storage;
        window.localStorage.setItem(key, storage.join(','));
    },
    /**
     * 数组去重
     */
    unique: function (arr) {
        var res = [],
            json = {};

        for (var i in arr) {
            if (!json[arr[i]]) {
                res.push(arr[i]);
                json[arr[i]] = 1;
            }
        }
        return res;
    },
    renderNavs: function (cur) {
        var content = $('#content'),
            data = {
                list: this.curTabs,
                curTab: cur
            },
            navs = $('#navs').html(),
            html = juicer(navs, data);

        content.after(html);
    },
    toArray: function (s) {
        if (s.length) {
            return Array.prototype.slice.call(s);
        } else {
            var arr = [];
            for (var i in s) {
                arr[i] = s[i];
            }
            return arr;
        }
    },
    // 判断是否是安卓
    isAndroid: function () {
        return (/android/gi).test(navigator.appVersion);
    },
    /**
     * 对于用户在Input框里面的输入进行过滤，例如 1、对于IOS六分之一空格情况 2、对于IOS输入中文时候会连续两次触发onInput情况
     *
     * @param str {String} 从input框读取的值
     * @returns {String} 返回过滤后的值
     *
     */
    filterString: function (str, isAndroid) {
        if (!isAndroid) {
            // IOS下需要做的处理
            var cache = arguments.callee;
            var curTime = new Date().getTime() / 1000;
            // 当前搜索的字符串在这个delta
            // ms时间区间里面如果跟最近的一次搜索字符串相同，则不对该结果进行响应,解决情况2问题
            var delta_time = 400;
            // 保存当前的字符串信息
            var keyObj = {
                str: str,
                time: curTime
            };

            if (cache.keyObj && cache.keyObj.str == str) {
                // 读取最近一次的字符串信息
                var lastTime = cache.keyObj.time;
                if (curTime - lastTime < delta_time) {
                    // 此时可认为丢弃此次相同关键字查找
                    return false;
                }
                // 重新更新cache
                cache.keyObj = keyObj;
            } else {
                // 这是新开页面的第一次成功检索或者和最近一次检索的字符串是不一样的关键字
                // 更新cache
                cache.keyObj = keyObj;
            }

            // 接着进行六分之一空格的处理
            str = str.replace(/\u2006/g, "");
        }

        return str;
    },
    /** 动态设置title **/
    setDocumentTitle: function (title) {
        document.title = title;

        setTimeout(function () {
            //利用iframe的onload事件刷新页面
            document.title = title;
            var iframe = document.createElement('iframe');
            iframe.src = '//m.baidu.com/favicon.ico'; // 必须
            iframe.style.visibility = 'hidden';
            iframe.style.width = '1px';
            iframe.style.height = '1px';
            iframe.onload = function () {
                setTimeout(function () {
                    document.body.removeChild(iframe);
                }, 0);
            };
            document.body.appendChild(iframe);
        }, 0);
    },
    WEBSITE_API: "http://" + window.location.host + "/v2",
    QiniuDamain: "http://pub.huilaila.net/",
    ectouchPic: (location.host.indexOf('huilaila') == -1) ? "http://mall.afguanjia.com/" : "http://mall.huilaila.net/",
    ectouchUrl: (location.host.indexOf('huilaila') == -1) ? "http://mall.afguanjia.com/index.php?m=default" : "http://mall.huilaila.net/index.php?m=default",
    mapKey: "WWIGHGNBVAXgO0wOkRpv9XnhOw3X9pfA",
    /** 底部导航 **/
    curTabs: [{
        id: 1,
        val: '#ground',
        name: '广场',
        icon: 'icon-square_2',
        iconfill: 'icon-square_1',
        url: (location.host.indexOf('huilaila') == -1) ? "http://mall.afguanjia.com/" : "http://mall.huilaila.net/"
    },
        {
            id: 2,
            val: '#events',
            name: '活动',
            icon: 'icon-hd-hll2',
            iconfill: 'icon-hd-hll1',
            url: 'http://' + location.host + '/event-list.html'
        },
        {
            id: 3,
            val: 'mine',
            name: '我家',
            icon: 'icon-home_2',
            iconfill: 'icon-home_1',
            url: (location.host.indexOf('huilaila') == -1) ? "http://mall.afguanjia.com/index.php?m=default&c=user&a=index" : "http://mall.huilaila.net/index.php?m=default&c=user&a=index"
        }
    ]
};
module.exports = util;