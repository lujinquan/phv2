// 武房异动统计表
$("#yueQuery").click(function() {
    archives_data();
})
archives_data();
function archives_data(){
    var time_span = $('#timeYear').val().split('-');
	var months = 0;
	if(parseInt(time_span[1])<9){
		months = '0'+(parseInt(time_span[1]) + 1) ;
	}
	else{
		months =parseInt(time_span[1]) + 1;
	}
	if(parseInt(time_span[1])==12){
		months = '0'+1 ;
	}
    $('.DOwnerTyp').text($('#TubulationI option:selected').text());
    $('.OwnerID').text($("#OwnerTyp option:selected").text());
    $('.time').text(time_span[0]+'年'+time_span[1]+'月');
	$('.nexttime').text(time_span[0]+'年'+months+'月');
    $('#below_com').text($('#TubulationI option:selected').text()/* ||section_name */);
    
    var querytyp = $('#TubulationI').val();
    var tubulation = $('#OwnerTyp').val();
    var time = $('#timeYear').val();
    //console.log(time);
     $.ajax({
      type: "POST",
      url: "/admin.php/report/house/archives",
    async:true,// 同步异步
      data: {inst:tubulation,type:querytyp,month:time},
      success: function(res){
        //res=JSON.parse(res);
        //console.log(res);
        var add_number = res.data.below;
        var arr=res.data.top;
        //console.log(arr);
        var aIndex =[];
        if(add_number[2]){
          $('#below_one').text(add_number[2]);
        }else{
           $('#below_one').text(0);
        }
        if(add_number[3]){
          $('#below_two').text(add_number[3]);
        }else{
          $('#below_two').text(0);
        }
         if(add_number[1]){
          $('#below_thr').text(add_number[1]);
        }else{
          $('#below_thr').text(0);
        }

        for(var i in arr ){
           aIndex.push(i);
        }
        //console.log(aIndex);
        for(var a=0;a<aIndex.length;a++){
            $('.one').append($('.Wsdj').eq(0).clone());
            $('.Wsdj:gt(0)').show();
          for(var c=0;c<arr[aIndex[a]].length;c++){
                $('.Wsdj').eq(a+1).children().eq(c).text(arr[a][c]);
             // for(var c=0;c<arr[aIndex[a]].length;c++){
             //      $('.Wsdj').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
             //       if(c%4==0&&c!=0){
             //         $('.Wsdj').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
             //      }
             // }
          }
        }
         for(var a=0;a<aIndex.length;a++){
           $('.two').append($('.Syxz').eq(0).clone());
           $('.Syxz:gt(0)').show();
           for(var c=0;c<arr[aIndex[a]].length;c++){
                 $('.Wsdj').eq(a+1).children().eq(c).text(arr[a][c]);
              // for(var c=0;c<arr[aIndex[a]].length;c++){
              //      $('.Wsdj').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
              //       if(c%4==0&&c!=0){
              //         $('.Wsdj').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
              //      }
              // }
           }
         }
        for(var a=0;a<aIndex.length;a++){
           $('.three').append($('.gig').eq(0).clone());
           $('.gig:gt(0)').show();
           for(var c=0;c<arr[aIndex[a]].length;c++){
                 $('.Wsdj').eq(a+1).children().eq(c).text(arr[a][c]);
              // for(var c=0;c<arr[aIndex[a]].length;c++){
              //      $('.Wsdj').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
              //       if(c%4==0&&c!=0){
              //         $('.Wsdj').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
              //      }
              // }
           }
        }
      
    }
      
   });
}