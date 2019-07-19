layui.use(['jquery','form'], function() {
	var $ = layui.$,
        form = layui.form;
	 form.on('switch(switchTest)', function(data){
		if(data.elem.checked==true)
		{
			$(".j-cancel-show").show();
		}
		else{
			$(".j-cancel-show").hide();
		}
	  });
})