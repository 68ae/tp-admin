<?php
namespace app\admin\controller;

use think\Session;
use think\Request;
use think\Loader;
use think\Db;

/**
* 角色管理
* @author aierui github  https://github.com/Aierui
* @version 1.0 
*/
class Role extends Admin
{
    private $role;
    function _initialize()
    {
        parent::_initialize();
        $this->role = Db::table('bs_role');
    }

    /**
     * 列表
     */
    public function index()
    {
        if(request()->isAjax()){
            $data = $this->role->select();
            return $data;
        }
        return view();
    }

    /**
     * 添加
     */
    public function add()
    {
        if (request()->isPost()) {
            $data = request()->param();
            $res = $this->role->insert($data);
            if($res == 1){
                Loader::model('LogRecord')->record('角色管理-添加成功');
                return info('添加成功！',1);
            }else{
                Loader::model('LogRecord')->record('角色管理-添加成功 data='.serialize($data));
                return info('添加失败！',0);
            }
        }
        return $this->fetch('edit');
    }

    /**
     * 编辑
     */
    public function edit($id = 0)
    {
        if (request()->isPost()) {
            $data = request()->param();
            $res = $this->role->update($data);
            if($res == 1){
                Loader::model('LogRecord')->record('角色管理-编辑成功 ID='.$id);
                return info('修改成功！',1);
            }else{
                Loader::model('LogRecord')->record('角色管理-编辑失败 ID='.$id);
                return info('修改失败！',0);
            }
        }

        $data = $this->role->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 删除
     */
    public function delete($id = 0){
        if(empty($id)){
            return info('删除项不能为空！',0);
        }
        $result = $this->role->delete($id);
        if ($result > 0) {
            Loader::model('LogRecord')->record('角色管理-编辑失败 IDS='.serialize($id));
            return info('删除成功！',1);            
        }        
    }

    /**
     * 菜单授权
     */
    public function access_menu($id = 0)
    {
         $role_id = $id;
        // 保存授权
        if(request()->isPost()){
            $data = [];
            $data['id'] = input('post.role_id/d', 0);
            $data['node_id'] = input('post.node_id');
            $this->role->update($data);
            return info("授权成功！",1);

        }elseif(!is_numeric($role_id) || $role_id < 1){
            return info("角色ID不能为空！",0);
        }
        
        // 展示授权页面
        $menu = [];
        $role = $this->detail($role_id);
        $selectedNode = explode(',', $role['node_id']);
        
        $Module = Db::table('bs_menu');

        $list = $Module->field("id, title, pid, status, module")->order('pid, sort DESC, id')->select();
        foreach($list as $i=>$item){
            $menu[] = [
                'id' => $item['id'],
                'parent' => $item['pid'] == 0 ? '#' : $item['pid'],
                'text' => $item['title'],
                'state' => array('disabled' => $item['status'] == 0),
            ];
        }
        
        $node_list = $Module->query("SELECT id, title, pid, access, 'node' FROM bs_node ORDER BY pid, sort DESC, id");
        foreach($node_list as $i=>$item){
            $menu[] = [
                    'id' => $item['pid'].'_'.$item['id'], // 为了标记我是节点
                    'parent' => $item['pid'],
                    'text' => $item['title'],
                    'state' => array('disabled' => $item['access'] == -1, 'selected' => in_array($item['id'], $selectedNode)),
                    'icon' => ' ',
                ];
        }

        $menu = json_encode($menu, JSON_UNESCAPED_UNICODE);
        $this->assign(['list' => $menu, 'role_id' => $role_id]);
        
        return $this->fetch();
    }


    private function detail($id){
        if(is_numeric($id) && $id > 0){
            return $this->role->find($id);
        }
        return [];
    }


}