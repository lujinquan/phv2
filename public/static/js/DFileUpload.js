/**
 * Created by DDD on 2017/7/10.
 */
var fileTotall = {
    array:[],
    groupArray:[],
    getGroupArray:function(){

    },
    totalUpLoad:function(url){
        var length = this.totalArray.length;
        var formData = new FormData();
        console.log(this.totalArray);
        if(length == 0){
            //layer.msg('请选择文件！');
            return false;
        }else{
            for(var i = 0; i < length;i++){
                if(this.totalArray[i] != null){
                    formData.append(this.totalArray[i][this.totalArray[i].length-1],this.totalArray[i]);
                }
            }
            $.ajax({
                type:"post",
                url:url,
                data:formData,
                processData:false,
                contentType:false,
                success:function(res){
                    res = JSON.parse(res);
                    layer.msg(res.msg);
                }
            });
        }
    },
    arrayUpload:function(url){
        console.log(this.array);//图片命名规则
        var length = this.array.length;
        var formData = new FormData();
        if(length == 0){
            //layer.msg("请选择要上传的文件");
            return false;
        }else{
            var currentTitle = this.array[0].title,j = 0;
            for(var i = 0; i < length; i++){
                if(this.array[i] != null){
                    formData.append(this.array[i].title+"["+j+"]",this.array[i]);
                }
                if(this.array[i+1] != null){
                    if(currentTitle == this.array[i+1].title){
                        j++;
                    }else{
                        currentTitle = this.array[i+1].title;
                        j = 0;
                    }
                }else{
                    j++;
                }
            }
            $.ajax({
                type:"post",
                url:url,
                data:formData,
                processData:false,
                contentType:false,
                success:function(res){
                    res = JSON.parse(res);
                    layer.msg(res.msg);
                }
            });
        }
    },
    getArrayFormdata:function(){
       
        this.array.sort(function(a,b){
            return (a.title > b.title);
        })
        console.log(this.array);//图片命名规则
        var length = this.array.length;
        var formData = new FormData();
        if(length == 0){
            //layer.msg("请选择要上传的文件");
            return false;
        }else{
            var currentTitle = this.array[0].title,j = 0;
            for(var i = 0; i < length; i++){
                if(this.array[i] != null){
                    formData.append(this.array[i].title+"["+j+"]",this.array[i]);
                }
                if(this.array[i+1] != null){
                    if(currentTitle == this.array[i+1].title){
                        j++;
                    }else{
                        currentTitle = this.array[i+1].title;
                        j = 0;
                    }
                }else{
                    j++;
                }
            }
        }
        return formData;
    }
};
function file(opt){
    this.filesArray = [];
    this.size = opt.size||10240;
    this.url = opt.url;
    this.name = opt.button.substring(1);
    this.button = $(opt.button);
    this.show = $(opt.show);
    this.id = opt.show;
    this.UpButton = $(opt.upButton);
    this.ChangeOrderID = opt.ChangeOrderID;
    this.TitleName = opt.title;
    this.init();
}
file.prototype.init = function(){
    var that = this;
    this.fileShow();
    this.UpButton.off('click');
    this.UpButton.on('click',function(){
        that.upload();
    });
}
file.prototype.fileShow = function(){
    var that = this;
    this.button.off('change');
    this.button.on('change',function(){
        that.fileShowEvent();
    });
}
file.prototype.fileShowEvent = function(){
    var that = this;
    var files = this.button[0].files;
    for(var i = 0;i < files.length;i++){
        if(files[i].size/1024 > this.size){
            layer.msg('图片过大！');
            continue;
        }else if(this.filesArray.some(function(data){return data.name==files[i].name})){
            layer.msg('上传文件重复！');
            continue;
        }else{
            files[i].title = this.name;
            console.log(that.name);

            // this.filesArray.push(files[i]);
            // fileTotall.array.push(files[i]);
            // var reader = new FileReader();
            // reader.readAsDataURL(files[i]);
            // reader.onload = function(e){
            //     var newImg = $("<div style='display:inline-block;position:relative;'>\
            //                         <img style='height:100px;margin:10px 5px;' src='"+this.result+"'/>\
            //                         <img style='width:20px;position:absolute;top:0;right:-2px;cursor:pointer;box-shadow: 0 0 5px #ccc;border-radius: 50%;' src='/public/static/gf/icons/delete.png' class='img_close' />\
            //                     </div>");
            //     that.show.append(newImg);
            //     that.show.find('.img_close').off('click');
            //     that.show.find('.img_close').on('click',function(){
            //         that.remove($(this).parent().index(),$(this).index('.img_close'));
            //     });
            // }
            this.ImgToBase64(files[i],720,function(base64){
                var newImg = $("<div style='display:inline-block;position:relative;'>\
                    <img class='j-newImg' style='height:100px;margin:10px 5px;' src='"+base64+"'/>\
                    <img style='width:20px;position:absolute;top:0;right:-2px;cursor:pointer;box-shadow: 0 0 5px #ccc;border-radius: 50%;' src='/static/system/image/icon-cancel.png' class='img_close' />\
                </div>");
                that.show.append(newImg);
                that.show.find('.img_close').off('click');
                that.show.find('.img_close').on('click',function(){
                    that.remove($(this).parent().index(),$(this).index('.img_close'));
                });
                that.filesArray.push(that.dataURLtoFile(base64,that.name));
                fileTotall.array.push(that.dataURLtoFile(base64,that.name));
                console.log(that.filesArray);
            });
        }
    }
    this.button.val('');
    // console.log(this.filesArray);
}
file.prototype.upload = function(){
    var length = this.filesArray.length;
    var formData = new FormData();
    if(length == 0){
        //layer.msg('请选择文件！');
        return false;
    }else{
        for(var i = 0; i < length;i++){
            if(this.filesArray[i] != null){
                formData.append(this.name+"["+i+"]",this.filesArray[i]);
            }
        }
        formData.append("ChangeOrderID",this.ChangeOrderID);
        formData.append("title",this.TitleName);
        $.ajax({
            type:"post",
            url:this.url,
            data:formData,
            processData:false,
            contentType:false,
            success:function(data){
                
            }
        });
    }
}
file.prototype.remove = function(index,filetotall_index){
    this.filesArray.splice(index,1);
    this.show.find('div').eq(index).remove();
    fileTotall.array.splice(index,1);
}

file.prototype.ImgToBase64 = function(file, maxLen,callBack) {
    var img = new Image();
    var reader = new FileReader();//读取客户端上的文件
    reader.readAsDataURL(file);
    reader.onload = function () {
        var url = reader.result;//读取到的文件内容.这个属性只在读取操作完成之后才有效,并且数据的格式取决于读取操作是由哪个方法发起的.所以必须使用reader.onload，
        img.src = url;//reader读取的文件内容是base64,利用这个url就能实现上传前预览图片
    };
    img.onload = function () {
        //生成比例
        var width = img.width, height = img.height;
        //计算缩放比例
        var rate = 1;
        if (width >= height) {
            if (width > maxLen) {
                rate = maxLen / width;
            }
        } else {
            if (height > maxLen) {
                rate = maxLen / height;
            }
        };
        if(file.size > 1048576){
            img.width = width * rate;
            img.height = height * rate;
        }
        //生成canvas
        var canvas = document.createElement("canvas");
        var ctx = canvas.getContext("2d");
        canvas.width = img.width;
        canvas.height = img.height;
        // if(img.width < img.height){
        //     ctx.translate(img.width/2,img.height/2);
        //     ctx.rotate(Math.PI/2);
        //     ctx.translate(-img.width/2,-img.height/2);
        // }
        ctx.drawImage(img, 0, 0, img.width, img.height);
        var base64 = canvas.toDataURL('image/jpeg', 0.92);
        callBack(base64);
    }
}
// 回调函数 调用的参数怎么回事？？
file.prototype.drawImg = function(base64){
    var newImg = $("<div style='display:inline-block;position:relative;'>\
                        <img class='j-newImg' style='height:100px;margin:10px 5px;' src='"+base64+"'/>\
                        <img style='width:20px;position:absolute;top:0;right:-2px;cursor:pointer;box-shadow: 0 0 5px #ccc;border-radius: 50%;' src='/static/system/image/icon-cancel.png' class='img_close' />\
                    </div>");
    that.show.append(newImg);
    that.show.find('.img_close').off('click');
    that.show.find('.img_close').on('click',function(){
        that.remove($(this).parent().index(),$(this).index('.img_close'));
    });
}
file.prototype.dataURLtoFile = function(dataurl, filename) {//base64转换成文件
    if(dataurl.indexOf('data') == -1){
        return '';
    }else{
        var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while(n--){
          u8arr[n] = bstr.charCodeAt(n);
        }
        var file = new File([u8arr], filename+'.jpg', {type:mime});
        file.title = filename;
        return file;
    }
}