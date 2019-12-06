<?php
// +----------------------------------------------------------------------
// | 基于ThinkPHP5开发
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.mylucas.com.cn
// +----------------------------------------------------------------------
// | 基础框架永久免费开源
// +----------------------------------------------------------------------
// | Author: Lucas <598936602@qq.com>，开发者QQ群：*
// +----------------------------------------------------------------------
namespace app\common\model;

use app\common\model\SystemAnnexGroup as GroupModel;
use app\common\model\SystemAnnexType;
use think\Model;
use think\Image;
use think\File;
use Env;

/**
 * 附件模型
 * @package app\common\model
 */
class SystemAnnex extends Model
{
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function systemAnnexType()
    {
        return $this->hasOne('system_annex_type', 'id', 'data_id')->bind('file_type,file_name');
    }

    /**
     * [兼容旧版]附件上传
     * @param string $from 来源
     * @param string $group 附件分组,默认sys[系统]，模块格式：m_模块名，插件：p_插件名
     * @param string $water 水印，参数为空默认调用系统配置，no直接关闭水印，image 图片水印，text文字水印
     * @param string $thumb 缩略图，参数为空默认调用系统配置，no直接关闭缩略图，如需生成 500x500 的缩略图，则 500x500多个规格请用";"隔开
     * @param string $thumb_type 缩略图方式
     * @param string $input 文件表单字段名
     * @author Lucas <598936602@qq.com>
     * @return json
     */
    public static function upload($from = 'input', $group = 'sys', $water = '', $thumb = '', $thumbType = '', $input = 'file')
    {

        $param                  = [];
        $param['from']          = $from;
        $param['group']         = $group;
        $param['water']         = $water;
        $param['thumb']         = $thumb;
        $param['thumb_type']    = $thumbType;
        $param['input']         = $input;

        return self::fileUpload($param);

    }

    /**
     * [新]附件上传
     * @param string $from 来源
     * @param string $group 附件分组,默认sys[系统]，模块格式：m_模块名，插件：p_插件名
     * @param string $water 水印，参数为空默认调用系统配置，no直接关闭水印，image 图片水印，text文字水印
     * @param string $thumb 缩略图，参数为空默认调用系统配置，no直接关闭缩略图，如需生成 500x500 的缩略图，则 500x500多个规格请用";"隔开
     * @param string $thumb_type 缩略图方式
     * @param string $input 文件表单字段名
     * @param string $full_path 是否返回完整的文件路径(含域名)
     * @author Lucas <598936602@qq.com>
     * @return json
     */
    public static function fileUpload($param = [])
    {

        if (empty($param)) {
            $param = request()->param();
        }

        $from       = isset($param['from']) ? $param['from'] : 'input';
        $group      = isset($param['group']) ? $param['group'] : 'sys';
        $water      = isset($param['water']) ? $param['water'] : '';
        $thumb      = isset($param['thumb']) ? $param['thumb'] : '';
        $thumbType  = isset($param['thumb_type']) ? $param['thumb_type'] : config('upload.thumb_type');
        $input      = isset($param['input']) ? $param['input'] : 'file';
        $fullPath   = isset($param['full_path']) ? $param['full_path'] : false;

        switch ($from) {

            case 'kindeditor':

                $input = 'imgFile';

                break;

            case 'umeditor':

                $input = 'upfile';

                break;

            case 'ckeditor':

                $input = 'upload';

                break;

            case 'ueditor':

                $input = 'upfile';

                if (isset($_GET['action']) && $_GET['action'] == 'config') {

                    $content    = file_get_contents('./static/js/editor/ueditor/config.json');
                    $json       = preg_replace("/\/\*[\s\S]+?\*\//", "", $content);

                    echo json_encode(json_decode($json, 1), 1);

                    exit;
                }

                break;
            
            default:// 默认使用layui.upload上传控件
                break;
        }
        //halt($input);
        $iniFileSzie = ini_get('upload_max_filesize');
        //$file = request()->file($input);
        try{$file = request()->file($input);}catch(\Exception $e){return self::result('文件大小超过PHP'.$iniFileSzie.'限制！', $from);}
//halt($file);
        $data = [];
        if (empty($file)) {
            return self::result('未找到上传的文件(文件大小可能超过php.ini默认2M限制)！', $from);
        }

        if ($file->getMime() == 'text/x-php' || $file->getMime() == 'text/html') {
            return self::result('禁止上传php,html文件！', $from);
        }

        // 格式、大小校验
        if ($file->checkExt(config('upload.upload_image_ext'))) {

            $type = 'image';
            if (config('upload.upload_image_size') > 0 && !$file->checkSize(config('upload.upload_image_size')*1024)) {
                return self::result('上传的图片大小超过系统限制['.config('upload.upload_image_size').'KB]！', $from);
            }

        } else if ($file->checkExt(config('upload.upload_file_ext'))) {

            $type = 'file';
            if (config('upload.upload_file_size') > 0 && !$file->checkSize(config('upload.upload_file_size')*1024)) {
                return self::result('上传的文件大小超过系统限制['.config('upload.upload_file_size').'KB]！', $from);
            }

        } else if ($file->checkExt('avi,mkv')) {

            $type = 'media';

        } else {

            return self::result('非系统允许的上传格式！', $from);

        }

        // 文件存放路径
        $filePath = '/upload/'.$group.'/'.$type.'/';
        //halt($file);
        //取消已上传的附件检测功能
        // 如果文件已经存在，直接返回数据
        // $res = self::where('hash', $file->hash())->find();
        // if ($res) { 
        //     return self::result('文件已存在上传成功。', $from, 1, $res);
        // }

        // 执行上传
        $upfile = $file->rule('date')->move('.'.$filePath);
        //dump($upfile);
        //halt($upfile);
        if ( !is_file('.'.$filePath.$upfile->getSaveName()) ) {
            return self::result('文件上传失败！', $from);
        }

        $fileCount  = 1;
        $fileSize   = round($upfile->getInfo('size')/1024, 2);
        $AnnexModel = new SystemAnnexType;
        $data = [
            'name'      => $input,
            'file'      => $filePath.str_replace('\\', '/', $upfile->getSaveName()),
            'hash'      => $upfile->hash(),
            'data_id'   => $AnnexModel->where([['file_type','eq',$input]])->value('id'),
            'type'      => $type,
            'size'      => $fileSize,
            'group'     => $group,
            'ctime'     => request()->time(),
            //待解决的问题
            //dump(config('upload.upload_clear_time'));halt(request()->time() + config('upload.upload_clear_time'));
            'etime'     => request()->time() + 3600*24*7,
        ];

        // 记录入库
        $res = self::create($data);
        
        $group_info = GroupModel::where('name', $group)->find();
        if (!$group_info) {
            GroupModel::create(['name' => $group]);
        }

        $data['thumb'] = [];

        if ($type == 'image') {

            $image = \think\Image::open('.'.$data['file']);

            // 水印
            if (!empty($water) && $water != 'no') {

                if ($water == 'text') {// 传参优先

                    if (is_file('.'.config('upload.text_watermark_font'))) {

                        $image->text(config('upload.text_watermark_content'), '.'.config('upload.text_watermark_font'), config('upload.text_watermark_size'), config('upload.text_watermark_color'))
                        ->save('.'.$data['file']); 

                    }

                } else if($water == 'image') {

                    if (is_file('.'.config('upload.image_watermark_pic'))) {

                        $image->water('.'.config('upload.image_watermark_pic'), config('upload.image_watermark_location'), config('upload.image_watermark_opacity'))
                        ->save('.'.$data['file']); 

                    }

                } else if (config('upload.image_watermark') == 1) {// 未传参，图片水印优先[开启图片水印]

                    if (is_file('.'.config('upload.image_watermark_pic'))) {

                        $image->water('.'.config('upload.image_watermark_pic'), config('upload.image_watermark_location'), config('upload.image_watermark_opacity'))
                        ->save('.'.$data['file']); 

                    }

                } else if (config('upload.text_watermark') == 1) {// 开启文字水印

                    if (is_file('.'.config('upload.text_watermark_font'))) {

                        $image->text(config('upload.text_watermark_content'), '.'.config('upload.text_watermark_font'), config('upload.text_watermark_size'), config('upload.text_watermark_color'))
                        ->save('.'.$data['file']); 

                    }

                }

            }

            // 缩略图
            if (!empty($thumb) && $thumb != 'no') {

                $thumbs = [];

                if (strpos($thumb, 'x')) {// 传参优先

                    $thumbs = explode(';', $thumb);

                } else if (!empty(config('upload.thumb_size'))) {

                    $thumbs = explode(';', config('upload.thumb_size'));

                }

                foreach ($thumbs as $k => $v) {

                    $tSize = explode('x', strtolower($v));

                    if (!isset($tSize[1])) {
                        $tSize[1] = $tSize[0];
                    }

                    $newThumb = $data['file'].'_'.$tSize[0].'x'.$tSize[1].'.'.strtolower(pathinfo($upfile->getInfo('name'), PATHINFO_EXTENSION));

                    $image->thumb($tSize[0], $tSize[1], $thumbType)->save('.'.$newThumb);

                    $thumbSize = round(filesize('.'.$newThumb)/1024, 2);

                    $data['thumb'][$k]['type']      = 'image';
                    $data['thumb'][$k]['group']     = $group;
                    $data['thumb'][$k]['file']      = $newThumb;
                    $data['thumb'][$k]['size']      = $thumbSize;
                    $data['thumb'][$k]['hash']      = hash_file('md5', '.'.$newThumb);
                    $data['thumb'][$k]['ctime']     = request()->time();
                    $data['thumb'][$k]['data_id']   = input('param.data_id', 0);

                    $fileSize+$thumbSize;
                    $fileCount++;

                }
                // if (!empty($data['thumb'])) {
                //     self::insertAll($data['thumb']);
                // }
            }

        }
        
        // 附件分组统计
        GroupModel::where('name', $group)->setInc('count', $fileCount);
        GroupModel::where('name', $group)->setInc('size', $fileSize);

        runhook('system_annex_upload', $data);

        // 返回带域名的路径
        if ($fullPath) {
            $data['file'] = get_domain().$data['file'];
        }
        $data['id'] = $res['id'];

        return self::result('文件上传成功。', $from, 1, $data);

    }

    /**
     * favicon 图标上传
     * @return json
     */
    public static function favicon()
    {
        // $file = request()->file('upload');
        $data['file'] = '/favicon.ico';
        return self::result('文件上传成功。', 'input', 1, $data);
    }

    /**
     * 返回结果
     * @author Lucas <598936602@qq.com>
     * @return array|string
     */
    private static function result($info = '', $from = 'input', $status = 0, $data = [])
    {

        // 删除无关的数据
        unset($data['hash'], $data['group'], $data['ctime']);

        $arr = [];

        switch ($from) {

            case 'kindeditor':

                if ($status == 0) {

                    $arr['error'] = 1;
                    $arr['message'] = $info;  

                } else {

                    $arr['error'] = 0;
                    $arr['url'] = $data['file'];

                }

                break;

            case 'ckeditor':

                if ($status == 1) {
                    echo '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(1, "'.$data['file'].'", "");</script>';
                } else {
                    echo '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(1, "", "'.$info.'");</script>';
                }

                exit;

                break;

            case 'umeditor':
            case 'ueditor':

                if ($status == 0) {

                    $arr['message'] = $info;
                    $arr['state']   = 'ERROR';

                } else {

                    $arr['message'] = $info;
                    $arr['url']     = $data['file'];
                    $arr['state']   = 'SUCCESS';

                }

                echo json_encode($arr, 1);
                exit;

                break;
            
            default:

                $arr['msg']     = $info;
                $arr['code']    = $status;
                $arr['data']    = $data;

                break;
        }

        return $arr;

    }
    /**
     * [改变附件的格式]
     * @param  string $before [description]
     * @param  string $after  [description]
     * @param  array  $data   [description]
     * @return [type]         [description]
     */
    public static function changeFormat($data = [] , $complete = false){
        if($data){

            $result = self::with('system_annex_type')->where([['id','in',$data]])->field('id,data_id,file')->select();
            if($complete && $result){
                foreach ($result as &$v) {
                    $v['file'] = 'https://pro.ctnmit.com'.$v['file'];
                }
            }
            return $result;
        }else{
            return '';
        }
    }

    /**
     * 更新附件的过期时间
     * @param  [type] $file [值，多个值之间用逗号分隔或数组形式]
     * @param  string $type [名，默认id，可选hash，file]
     * @return [type]       [description]
     */
    public function updateAnnexEtime($file ,$type = 'id')
    {
        if(!in_array($type,['file','hash','id'])){
            return '参数名不合法！';
        }
        if(!$file){
            return '附件值不能为空！';
        }
        //halt($file);
        $res = self::where([[$type,'in',$file]])->update(['etime'=>0]); //将附件的过期时间设为永不过期
        if($res !== false){
            return '过期时间更新成功！';
        }else{
            return '过期时间更新失败！';
        }
    }
    
    /**
     * 清除过期附件
     */
    public function clearAnnex()
    {
        $curTime = time();
        $files = self::where([['etime','<',$curTime],['etime','neq',0]])->column('file');
        if($files){
            foreach($files as $file){
                @unlink($_SERVER['DOCUMENT_ROOT'].$file);
            }
            self::where([['etime','<',$curTime],['etime','neq',0]])->delete();
        }
    }

}
