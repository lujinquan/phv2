layui.use(['form','table','layer'], function(){
  var form = layui.form
      ,table = layui.table
	  ,$ = layui.jquery
	  ,layer = layui.layer;
  //监听指定开关
  form.on('switch(switchTest)', function(data){
    // console.log(data.elem.checked); 判断是否选中
    if(data.elem.checked==true)
	{
		$(".j-cancel-show").show();
	}
	else
	{
		$(".j-cancel-show").hide();
	}
  });
  
});