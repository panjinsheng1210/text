<?php
/**
 * Created by PhpStorm.
 * User: zhangchong
 * Date: 2019/8/30
 * Time: 9:29
 */
namespace app\common\model;
use think\Validate;

class LeaveMessage extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


    /**
     * @param $post
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $tableWhere['where'][] = ['type','=',1];
        $list = $this->where($tableWhere['where'])->order('id desc')->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;

        return $re;
    }


    /**
     *  回复留言
     * User:tianyu
     * @param array $data
     * @return array
     */
    public function addData($data = [])
    {
        $result = ['status' => true, 'msg' => '留言成功' , 'data' => ''];

        $validate = new Validate($this->rule,$this->msg);
        if(!$validate->check($data))
        {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        } else {
            if (!$this->allowField(true)->save($data)) {
                $result['status'] = false;
                $result['msg'] = '留言失败';
            }
        }
        return $result;
    }

    /**
     *  修改公告
     * User:tianyu
     * @param array $data
     * @return array
     */
    public function saveData($data = [])
    {
        $result = ['status' => true, 'msg' => '回复成功', 'data' => ''];

        $validate = new Validate($this->rule, $this->msg);
        if (!$validate->check($data)) {
            $result['status'] = false;
            $result['msg']    = $validate->getError();
        } else {
            if ($this->allowField(true)->save($data, ['id' => $data['id']]) === false) {
                $result['status'] = false;
                $result['msg']    = '回复失败';
            }
        }
        return $result;
    }


    /**
     *  WHERE 搜索条件
     * User:tianyu
     * @param $post
     * @return mixed
     */
    protected function tableWhere($post)
    {
        $where = [];
        if(isset($post['title']) && $post['title'] != ""){
            $where[] = ['title', 'like', '%'.$post['title'].'%'];
        }
        if(isset($post['ctime']) && $post['ctime'] != "") {
            $date_array = explode('~',$post['ctime']);
            $stime = strtotime($date_array[0].'00:00:00',time());   //从当天0点开始
            $etime = strtotime($date_array[1].'23:59:59',time());   //当天最后时间
            $where[] = ['ctime',['EGT',$stime],['ELT',$etime],'and'];
        }
        if(isset($post['role'])&&$post['role']){
            if($post['role']==1){
                //未回复
                $where[] = ['reply_id','=',0];
            }elseif($post['role']==2){
                //已回复
                $where[] = ['reply_id','>',0];
            }
        }
        if(isset($post['mobile'])&&$post['mobile']){
            $user = new User();
            $user_id = $user->where('mobile',$post['mobile'])->find()->id;
            $where[] = ['user_id','=',$user_id];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = ['sort ASC'];
        return $result;
    }


    /**
     * 根据查询结果，格式化数据
     * @author sin
     * @param $list
     * @return mixed
     */
    protected function tableFormat($list)
    {
        foreach($list as $val)
        {
            $val['mobile'] = (new User())->where('id',$val['user_id'])->find()->mobile;
            $val['ctime'] = getTime($val['ctime']);
            $val['utime'] = getTime($val['utime']);
        }
        return $list;
    }

    /*
     * 取留言的具体信息
     */

    public function getMessageInfo ($id)
    {
        $return_data = [
            'status' => true,
            'msg'    => '获取成功',
            'data'   => []
        ];
        $data = array();
        $reply = array();
        $send = array();
        $list = $this->where('id',$id)->find();
        if($list['type']==1){
            //查找回复信息
            $data['send'] = $list;
            if($list['reply_id']){
                $reply = $this->where('id',$list['reply_id'])->find();
            }
            $data['reply'] = $reply;
        }elseif($list['type']==2){
            //查找发送的内容
            $send = $this->where('reply_id',$list['id'])->find();
            $data['send'] = $send;
            $data['reply'] = $list;
        }
        $return_data['data'] = $data;
        return $return_data;
    }

    /**
     * 前台添加留言
     * @param $user_id  用户id
     * @param $title    标题
     * @param $content  内容
     * @return array
     */
    public function sendMessage($user_id,$title,$content){
        $result = ['status' => true, 'msg' => '留言成功', 'data' => ''];
        $data['user_id'] = $user_id;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['type'] = 1;
        if($this->save($data)==false){
            $result['status'] = false;
            $result['msg'] = '留言失败';
        }
        return $result;
    }

    /**
     * 留言记录
     * @param $user_id
     * @param $postWhere
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($user_id,$postWhere,$order = 'id desc', $page = 1, $limit = 10){
        $return_data = [
            'status' => true,
            'msg'    => '查询成功',
            'data'   => []
        ];
        $where[] = ['user_id', '=', $user_id];
        if(isset($postWhere['type'])&&$postWhere['type']){
            //取出来收件箱记录
            $where[] = ['type','=',$postWhere['type']];
        }
        $list  = $this
            ->where($where)
            ->order($order)
            ->page($page, $limit)
            ->select();
        $total = $this
            ->where($where)
            ->count();
        $return_data['data']['list'] = $this->tableFormat($list);
        $return_data['data']['total'] = $total;
        return $return_data;
    }
    public function userInfo()
    {
        return $this->hasOne('User','id','user_id')->bind([
            'mobile'
        ]);
    }
    /**
     * 回复设置
     * @param $message_id
     * @param $title
     * @param string $content
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function replyMessage($message_id,$title,$content)
    {
        $return = [
            'status' => false,
            'msg' => ''
        ];
        //获取留言信息
        $message_info = $this->where(['id'=>$message_id])->find();
        $user_id = $message_info->user_id;
        //插入记录
        $data = [
            'user_id' => $user_id,
            'type' => 2,
            'title'=>$title,
            'content'=>$content,
            'ctime' => time(),
            'utime' => time()
        ];
        $insert_id = $this->insertGetId($data);
        //插入主表
        $message_info->reply_id = $insert_id;
        if($message_info->save()&&$insert_id){
            $return['status'] = true;
            $return['msg'] = '回复成功';
        }
        return $return;
    }

}