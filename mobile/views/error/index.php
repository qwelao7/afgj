<?php
\Yii::error('from 404 page error');
\Yii::error(\Yii::$app->errorHandler->exception)
?>
<ion-nav-bar class="bar-stable">
</ion-nav-bar>
<ion-nav-view>
    <ion-view hide-nav-bar="true">
        <ion-content>
            <div style="margin-top:20%;color: #333338;text-align: center;vertical-align: middle">
                <p style="font-size: 15px">亲爱的朋友</p>
                <p style="font-size: 14px">您访问的页面来自 <span style="font-size: 18px">“</span> <span style="font-size: 18px;color: #009042">回来啦社区</span> <span style="font-size: 18px">”</span>公众号</p>
                <img src="../../assets/css/images/weixin_code.png" style="width: 50%">
                <p style="color:#009042;font-size: 24px;font-weight: bold">请长按二维码</p>
                <p style="color:#009042;font-size: 15px">关注“回来啦”，享受美好生活服务</p>
            </div>
        </ion-content>
    </ion-view>
</ion-nav-view>