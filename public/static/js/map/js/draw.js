  
	//画圈按钮  
  var drawBtn = document.getElementById('draw');
  //退出画圈按钮
  var exitBtn = document.getElementById('exit');
  //画圈完成的数据展示列表
  var oUl = document.getElementById('data');

  /**画圈有关的数据结构**/
  // 是否处于画圈状态下
  var isInDrawing = false;
  // 是否处于鼠标左键按下的状态
  var isMouseDown = false;
  //存储画出折线点的数组
  var polyPointArray = [];
  //上次操作画出的折线
  var lastPolyLine = null;
  //画圈完成后生成的多边形
  var polygonAfterDraw = null;
  //存储地图上marker的数组
  var markerList = [];
// 百度地图API功能
window.onload = function(){
	var map = new BMap.Map("allmap");    // 创建Map实例
	map.centerAndZoom(new BMap.Point(114.45326,30.526532), 12);  // 初始化地图,设置中心点坐标和地图级别
	//map.addControl(new BMap.MapTypeControl());   //添加地图类型控件
	map.addControl(new BMap.NavigationControl({enableGeolocation:true}));
	map.addControl(new BMap.OverviewMapControl());
	map.setCurrentCity("武汉");          // 设置地图显示的城市 此项是必须设置的
	map.enableScrollWheelZoom(true);     //开启鼠标滚轮缩放
	initMapMarkers(map);
	bindEvents(map);
	}
	//===================
function initMapMarkers(map){
  
	// var xy = [
	// 	{'x':114.257789,'y':30.628016,'z':'武汉市1'},
	// 	{'x':114.45326,'y':30.526532,'z':'武汉市2'},
	// 	{'x':114.45326,'y':30.496664,'z':'武汉市3'},
	// 	{'x':114.653331,'y':30.650885,'z':'武汉市4'},
	// 	{'x':114.093363,'y':30.712504,'z':'武汉市5'},
	// 	{'x':114.577442,'y':30.263371,'z':'武汉市6'}
	// ];

	var markers = [];
	var pt = null;
	for (var i in xy) {
	   pt = new BMap.Point(xy[i].x , xy[i].y);
	   var marker = new BMap.Marker(pt);
	   var label = new BMap.Label(xy[i].z , { offset: new BMap.Size(20, -10) });
	   marker.setLabel(label);
	   markerList.push(marker);
		 
	}
	//最简单的用法，生成一个marker数组，然后调用markerClusterer类即可。
	var markerClusterer = new BMapLib.MarkerClusterer(map,
		{
			markers:markerList,
			girdSize : 100,
			styles : [{
	            url:'/static/js/map/img/red.png',
	            size: new BMap.Size(92, 92),
				backgroundColor : '#2E77EF'
			}],
		});
	markerClusterer.setMaxZoom(13);
	markerClusterer.setGridSize(100);
}

// 绑定事件
  function bindEvents(map) {
    //开始画圈绑定事件
    drawBtn.addEventListener('click', function (e) {
			if(map.getZoom()<= 12)
			{
				 layer.msg("请放大地图后使用画图找房！");
			}
			else{
				  //$(".BMapLabel").hide();
					$("#draw").hide();
					$("#exit").show();
					//禁止地图移动点击等操作
					map.disableDragging(); //	禁用地图拖拽
					map.disableScrollWheelZoom();// 禁用滚轮放大缩小
					map.disableDoubleClickZoom();// 禁用双击放大
					map.disableKeyboard();// 禁用键盘操作
					map.setDefaultCursor('crosshair'); // 设置地图默认的鼠标指针样式。参数cursor应符合CSS的cursor属性规范
					//设置标志位进入画圈状态
					isInDrawing = true;    
			}
    });
    //退出画圈按钮绑定事件
    exitBtn.addEventListener('click', function (e) {
			$(".BMapLabel").show();
			$("#draw").show();
			$("#exit").hide();
      //恢复地图移动点击等操作
      map.enableDragging(); // 启用地图拖拽
      map.enableScrollWheelZoom();
      map.enableDoubleClickZoom();
      map.enableKeyboard();
      map.setDefaultCursor('default');
      //设置标志位退出画圈状态
      isInDrawing = false;
    })
    
    map.addEventListener('mousedown', function () {
      //如果处于画圈状态下,清空上次画圈的数据结构,设置isMouseDown进入画圈鼠标按下状态
      if (isInDrawing) {
        //清空地图上画的折线和圈
        map.removeOverlay(polygonAfterDraw);
        map.removeOverlay(lastPolyLine);
        polyPointArray = []; // 清空画出折线点的数组
        lastPolyLine = null; // 清除上次的画出的折线条
        isMouseDown = true;  
      }
    })
    
    map.addEventListener('mousemove', function (e) {
      //如果处于鼠标按下状态,才能进行画操作
      if (isMouseDown) {
        //将鼠标移动过程中采集到的路径点加入数组保存
        polyPointArray.push(e.point);
        //除去上次的画线
        if (lastPolyLine) {
          map.removeOverlay(lastPolyLine)
        }
        //根据已有的路径数组构建画出的折线
        var polylineOverlay = new window.BMap.Polyline(polyPointArray, {
          strokeColor: '#00ae66',
          strokeOpacity: 1,
          enableClicking: false
        });
        //添加新的画线到地图上
        map.addOverlay(polylineOverlay);
        //更新上次画线条
        lastPolyLine = polylineOverlay
      }
    })
    
    map.addEventListener('mouseup', function (e) {
      //如果处于画圈状态下 且 鼠标是按下状态
      if (isInDrawing && isMouseDown) {
        //退出画线状态
        isMouseDown = false;
        //添加多边形覆盖物,设置为禁止点击
        var polygon = new window.BMap.Polygon(polyPointArray, {
          strokeColor: '#00ae66',
          strokeOpacity: 1,
          fillColor: '#00ae66',
          fillOpacity: 0.3,
          enableClicking: false
        });
        map.addOverlay(polygon);
        //保存多边形,用于后续删除该多边形
        polygonAfterDraw = polygon
        //计算房屋对于多边形的包含情况
        var ret = caculateEstateContainedInPolygon(polygonAfterDraw);
				console.log(ret);
        //更新dom结构
        oUl.innerHTML = '';
        var fragment = document.createDocumentFragment();
        for (var i = 0; i < ret.length; i++) {
          var li = document.createElement('li');
          li.innerText ? li.innerText = ret[i] : li.textContent = ret[i];
          //fragment.appendChild(li);  //展示所选内容
		      //console.log(li.innerText);   //打印所选内容
        }
        oUl.appendChild(fragment);
		map.setZoom(14);//画圈完成后改变地图缩放比例找到圈内数据
      }
    });
  }

  //判定一个点是否包含在多边形内
  function isPointInPolygon(point, bound, pointArray) {
    //首先判断该点是否在外包矩形内，如果不在直接返回false
    if (!bound.containsPoint(point)) {
      return false;
    }
    //如果在外包矩形内则进一步判断
    //该点往右侧发出的射线和矩形边交点的数量,若为奇数则在多边形内，否则在外
    var crossPointNum = 0;
    for (var i = 0; i < pointArray.length; i++) {
      //获取2个相邻的点
      var p1 = pointArray[i];
      var p2 = pointArray[(i + 1) % pointArray.length];
      //lng是经度，lat是纬度
      //如果点坐标相等直接返回true
      if ((p1.lng === point.lng && p1.lat === point.lat) || (p2.lng === point.lng && p2.lat === point.lat)) {
        return true
      }
      //如果point在2个点所在直线的下方则continue
      if (point.lat < Math.min(p1.lat, p2.lat)) {
        continue;
      }
      //如果point在2个点所在直线的上方则continue
      if (point.lat >= Math.max(p1.lat, p2.lat)) {
        continue;
      }
      //有相交情况:2个点一上一下,计算交点
      //特殊情况2个点的横坐标相同
      var crossPointLng;
      //如果线段2个点x相同，则斜率无穷大，特殊处理
      if (p1.lng === p2.lng) {
        crossPointLng = p1.lng;
      } else {
        //计算2个点的斜率
        var k = (p2.lat - p1.lat) / (p2.lng - p1.lng);
        //得出水平射线与这2个点形成的直线的交点的横坐标
        crossPointLng = (point.lat - p1.lat) / k + p1.lng;
      }
      //如果crossPointLng的值大于point的横坐标则算交点(因为是右侧相交)
      if (crossPointLng > point.lng) {
        crossPointNum++;
      }

    }
    //如果是奇数个交点则点在多边形内
    return crossPointNum % 2 === 1
  }

  //计算地图上点的包含状态
  function caculateEstateContainedInPolygon(polygon) {
    //得到多边形的点数组
    var pointArray = polygon.getPath();
    //console.log(pointArray,'pointArray')   画圈经纬度
    //获取多边形的外包矩形
    var bound = polygon.getBounds();
    //在多边形内的点的数组
    var pointInPolygonArray = [];
    //计算每个点是否包含在该多边形内
    for (var i = 0; i < markerList.length; i++) {
      //返回该marker的坐标点的地理坐标
      var markerPoint = markerList[i].getPosition();
      if (isPointInPolygon(markerPoint, bound, pointArray)) {
        pointInPolygonArray.push(markerList[i])
      }
    }
    var estateListAfterDrawing = pointInPolygonArray.map(function (item) {
      return item.getLabel().getContent()
    })
    return estateListAfterDrawing
}
//左边详情显示隐藏
$(document).ready(function() { 
		//$('#j-houseList').addClass("active");
		var h=document.documentElement.clientHeight;//可见区域高度
		document.getElementById('j-houseList').style.height=h-170+"px";
		document.getElementById('wrapper').style.height=h-170+"px";
		document.getElementById('allmap').style.height=h-170+"px";
		
	});
$('.close_list').click(function(event) {
	var ck=document.getElementById('close_list');
	if (ck.style.display=='block') {
		$('#j-houseList').addClass("active"); 
		$(".close_list i").removeClass("j-icon-youkuohao");
		$(".close_list i").addClass("j-icon-zuokuohao");
		ck.style.display='';
	}else{
		$('#j-houseList').removeClass("active"); 
		$(".close_list i").removeClass("j-icon-zuokuohao");
		$(".close_list i").addClass("j-icon-youkuohao");
		ck.style.display='block';
	}
});
//点击改变label颜色
  $(".map-container").on("click",".BMapLabel",function(){
		//详情显示
		$(".j-houseList").addClass("active");
		$(".j-houseList .close_list").css("display","");
		$(".close_list i").removeClass("j-icon-youkuohao");
		$(".close_list i").addClass("j-icon-zuokuohao");
		//当前点击的label
  	$(this).addClass("on").siblings().removeClass("on");
		if($(this).hasClass("on"))
		{
			$(".BMapLabel").css("background-color","#2E77EF");
			$(".BMapLabel span").css("color","#2E77EF");
			$(this).css("background-color","#f60");
			$(this).find("span").css("color","#f60");
		}
		$("#address").text($(this).find("p").text());
  })
