		layui.use(['jquery'], function() {
		   var $ = layui.$;
		// 百度地图API功能
        var map = new BMap.Map("allmap");
        //var map = new BMap.Map("allmap", { mapType: BMAP_SATELLITE_MAP });
        var point = new BMap.Point(114.334286,30.560728);
        map.centerAndZoom(point, 14);
        map.enableScrollWheelZoom();                  //启用滚轮放大缩小
        //定位
        var geolocation = new BMap.Geolocation();
        geolocation.getCurrentPosition(function (r) {
            if (this.getStatus() == BMAP_STATUS_SUCCESS) {
                var mk = new BMap.Marker(r.point);
                map.addOverlay(mk);
                map.panTo(r.point);
                //mk.setAnimation(BMAP_ANIMATION_BOUNCE); //跳动的动画
                mk.enableDragging();
                //alert('您的位置：' + r.point.lng + ',' + r.point.lat);
                document.getElementById("jingdu").value = r.point.lng;
                document.getElementById("weidu").value = r.point.lat;
            }
            else {
                //alert('failed' + this.getStatus());
            }
        }, { enableHighAccuracy: true })

        //add city
        map.addControl(new BMap.CityListControl({
            anchor: BMAP_ANCHOR_TOP_LEFT
        }));

        //add click
        function showInfo(e) {
            //alert(e.point.lng + ", " + e.point.lat);
            document.getElementById("jingdu").value = e.point.lng;
            document.getElementById("weidu").value = e.point.lat;
            var mk = new BMap.Marker(e.point);
            map.addOverlay(mk);
            map.panTo(e.point);
            //mk.setAnimation(BMAP_ANIMATION_BOUNCE); //跳动的动画


            deletePoint(); //删除所有标注
        }
        map.addEventListener("click", showInfo);

        function deletePoint() 
        {
            var allOverlay = map.getOverlays();
            for (var i = 0; i < allOverlay.length - 1; i++) {
                map.removeOverlay(allOverlay[i]);
            }
        }
        //关于状态码
        //BMAP_STATUS_SUCCESS    检索成功。对应数值“0”。
        //BMAP_STATUS_CITY_LIST    城市列表。对应数值“1”。
        //BMAP_STATUS_UNKNOWN_LOCATION    位置结果未知。对应数值“2”。
        //BMAP_STATUS_UNKNOWN_ROUTE    导航结果未知。对应数值“3”。
        //BMAP_STATUS_INVALID_KEY    非法密钥。对应数值“4”。
        //BMAP_STATUS_INVALID_REQUEST    非法请求。对应数值“5”。
        //BMAP_STATUS_PERMISSION_DENIED    没有权限。对应数值“6”。(自 1.1 新增)
        //BMAP_STATUS_SERVICE_UNAVAILABLE    服务不可用。对应数值“7”。(自 1.1 新增)
        //BMAP_STATUS_TIMEOUT    超时。对应数值“8”。(自 1.1 新增)

        function codeAddress() {
            // 创建地址解析器实例
            var myGeo = new BMap.Geocoder();
            // 将地址解析结果显示在地图上,并调整地图视野
            myGeo.getPoint(document.getElementById("address").value, function (point) {
                if (point) {
                    map.centerAndZoom(point, 14);
                    map.addOverlay(new BMap.Marker(point));
                    document.getElementById("jingdu").value = point.lng;
                    document.getElementById("weidu").value = point.lat;
                } else {
                    alert("您输入的地址在地图中未找到，请重新输入地址!");
                }
            }, "");
        }
});