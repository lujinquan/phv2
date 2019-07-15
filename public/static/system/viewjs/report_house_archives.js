/*房屋统计*/
window.onload = function () {
var section_name = $('#userName').text();
var section_name_start = section_name.indexOf('(')+1;
var section_name_end = section_name.indexOf(')');
section_name = section_name.substring(section_name_start,section_name_end);
var time_span = $('#timeYear').val().split('-');
$('.time').text(time_span[0]+'年'+time_span[1]+'月');
};
$("#yueQuery").click(function() {
    $('.Wsdj:gt(0)').remove();
    $('.Syxz:gt(0)').remove();
    $('.gig:gt(0)').remove();
    $('.jsnd:gt(0)').remove();
    $('.fwjz:gt(0)').remove();
    $('.phpHide').hide();
    var OwnerTyp_number = $('#OwnerTyp').val();
    var QueryTyp_number = $('#QueryTyp').val();
    var TubulationI_number = $('#TubulationI option:selected').val();
    console.log(TubulationI_number);
   var time_span = $('#timeYear').val().split('-');
    //$('#DOwnerTyp').text($('#OwnerTyp option').eq(OwnerTyp_number).text());
    $('#DOwnerTyp').text($('#OwnerTyp option:selected').text());
    $('.DQueryType').text($('#QueryTyp option').eq(QueryTyp_number).text());
    $('.time').text(time_span[0]+'年'+time_span[1]+'月');
    $('#below_com').text($('#TubulationI option:selected').text()||section_name);
    
    var owner = $('#OwnerTyp').val();
    var tubulation = $('#TubulationI').val();
    var querytyp = $('#QueryTyp').val();
    var time = $('#timeYear').val();
    if (querytyp == 1) {
        $('.one').show();
        $('.two').hide();
        $('.three').hide();
        $('.four').hide();
        $('.five').hide();
    } else if (querytyp == 2) {
        $('.one').hide();
        $('.two').show();
        $('.three').hide();
        $('.four').hide();
        $('.five').hide();
    } else if (querytyp == 3) {
        $('.one').hide();
        $('.two').hide();
        $('.three').show();
        $('.four').hide();
        $('.five').hide();
    } else if (querytyp == 4) {
        $('.one').hide();
        $('.two').hide();
        $('.three').hide();
        $('.four').show();
        $('.five').hide();
    } else {
        $('.one').hide();
        $('.two').hide();
        $('.three').hide();
        $('.four').hide();
        $('.five').show();
    }

    // $.post('http://xd.oaopen.com/interface/login.php',{user:'12210' ,pwd:45451},function(res){
    //
    //     console.log(res);
    // })

    // $.post('',{querytyp:querytyp ,usenatur:usenatur ,damage:damage ,tubulation:tubulation ,owner:owner},function(res){

    //     console.log(res);
    // })
   console.log(querytyp);
     $.ajax({
      type: "POST",
      url: "/ph/Api/queryHouseReport",
	  async:true,// 同步异步
      data: {OwnerType:owner,TubulationID:tubulation,QueryType:querytyp,month:time},
      success: function(res){
        res=JSON.parse(res);
        console.log(res);
        var add_number = res.data.data.below;
        var arr=res.data.data.top;
          console.log(arr);
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
        console.log(aIndex);
        for(var a=0;a<aIndex.length;a++){
            $('.one').append($('.Wsdj').eq(0).clone());
            $('.Wsdj:gt(0)').show();
          for(var c=0;c<arr[aIndex[a]].length;c++){
                $('.Wsdj').eq(a+1).children().eq(c).text(arr[a][c]);
             for(var c=0;c<arr[aIndex[a]].length;c++){
                  $('.Wsdj').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
                   if(c%4==0&&c!=0){
                     $('.Wsdj').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
                  }
             }
          }
        }

         for(var a=0;a<aIndex.length;a++){
           $('.two').append($('.Syxz').eq(0).clone());
           $('.Syxz:gt(0)').show();
           for(var c=0;c<arr[aIndex[a]].length;c++){
              $('.Syxz').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
              if(c==5||c==11||c==16||c==21){
                $('.Syxz').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
              }
           }
         }

        for(var a=0;a<aIndex.length;a++){
           $('.three').append($('.gig').eq(0).clone());
           $('.gig:gt(0)').show();
           for(var c=0;c<arr[aIndex[a]].length;c++){
                $('.gig').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
                if(c==4||c==9||c==15||c==20){
                  $('.gig').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]+'%');
                }
           }
        }

        for(var a=0;a<aIndex.length;a++){
          $('.four').append($('.jsnd').eq(0).clone());
          $('.jsnd:gt(0)').show();
           for(var c=0;c<arr[aIndex[a]].length;c++){
                $('.jsnd').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
                if(c%4==0&&c!=0){
                   $('.jsnd').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]+'%');
                }
           }
        }

         for(var a=0;a<aIndex.length;a++){
          $('.five').append($('.fwjz').eq(0).clone());
          $('.fwjz:gt(0)').show();
           for(var c=0;c<arr[aIndex[a]].length;c++){
                $('.fwjz').eq(a+1).children().eq(c).text(arr[aIndex[a]][c]);
                // if(c%4==0&&c!=0){
                //    $('.fwjz').eq(a+1).children().eq(c).text(arr[a][c]+'%');
                // }
           }
        }
      
    }
      
   });
})