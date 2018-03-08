module.exports = {
    /**
     * 添加统计
     */
    pings: function () {
        var string = '<script type="text/javascript" src="http://pingjs.qq.com/h5/stats.js" name="MTAH5" sid="500149441"></script>';
        var objE = document.createElement("div");
        objE.innerHTML = string;
        var oBody = document.getElementsByTagName('body').item(0);
        oBody.appendChild(objE.childNodes[0]);
    },
    appkey: '23444086',
    password: 'abc123',
    customService: 'hlladmin',
    complainGroup: 161112623,
    customGroup: 161109880,
    //绿城客服
    homeMember: 'cntaobaohlladmin:lchm',
    defaultHeadImg: 'https://gw.alicdn.com/tps/TB10C4vKXXXXXa_aXXXXXXXXXXX-420-420.jpg_200x200.jpg',
    defaultCommunityImg: 'defaultpic/fangzi.jpg',
    captchaId: '3c39aa8a680441229334259ec403ea47',
    // 跨域ajax，大数据页面使用
    ajax_data:'http://ces.huilaila.net'
};