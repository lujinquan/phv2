function G(id) {
    return document.getElementById(id);
}
var lng = document.getElementById('jingdu');
var lat = document.getElementById('weidu');
var map = new BMap.Map("allmap");
var point = new BMap.Point(114.334286,30.560728);
map.centerAndZoom(point,13);
map.enableScrollWheelZoom(true);// 允许鼠标滑轮放大缩小 
 
var ac = new BMap.Autocomplete(    //建立一个自动完成的对象
    {"input" : "suggestId"
    ,"location" : map
});
 
ac.addEventListener("onhighlight", function(e) {  //鼠标放在下拉列表上的事件
	
	var str = "";
    var _value = e.fromitem.value;
    var value = "";
    if (e.fromitem.index > -1) {
        value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
    }    
    str = "FromItem<br />index = " + e.fromitem.index + "<br />value = " + value;
    
    value = "";
    if (e.toitem.index > -1) {
        _value = e.toitem.value;
        value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
    }    
});
 
var myValue;
ac.addEventListener("onconfirm", function(e) {    //鼠标点击下拉列表后的事件
	lng.value = '';
	lat.value = '';
	var _value = e.item.value;
    myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
    
    setPlace();
});
 
function setPlace(){// 创建地址解析器实例
var myGeo = new BMap.Geocoder();// 将地址解析结果显示在地图上,并调整地图视野
myGeo.getPoint(myValue, function(point){
  if (point) {
    map.centerAndZoom(point, 16);
    map.addOverlay(new BMap.Marker(point));
  }
}, "武汉");
}
 
 
map.addEventListener('click', function (e) {
 
	lng.value = e.point.lng;
	lat.value = e.point.lat;
})
 
 
//定位到当前位置
var geolocation = new BMap.Geolocation();
geolocation.getCurrentPosition(function(r){
	/* document.getElementById("jingdu").value = r.point.lng;//当前经度
	document.getElementById("weidu").value = r.point.lat;//当前纬度 */
	if(this.getStatus() == BMAP_STATUS_SUCCESS){
		var mk = new BMap.Marker(r.point);
		map.addOverlay(mk);
		map.panTo(r.point);
		/* alert('您的位置：'+r.point.lng+','+r.point.lat); */	
	}
	else {
		alert('failed'+this.getStatus());
	}        
});