<?php 

namespace Home\Controller;

use Think\Controller;

/**
 * 获取商品分类
 */
class ClassController extends CommonController {

    /**
     * 分类导航栏
     * @return [type] [description]
     */
    public function navigation() {


        $data = D('class')->getClassList(-1,'', '');
        $this->ret($data);
    }


    /**
     * 二级分类,需要二级分类标题(需要获取三级分类)
     */
    public function category() {

        $fid    =I('get.fid', -1, 'intval');
        if ($fid < 1) {
            $this->ret('参数错误');
        }

        // 获取二级分类标题
        $fileds = array('id', 'class_name', 'fid');
        $cate   = D('class')->getClassList($fid, $fileds, '');
        if (is_array($cate)) {
            $data = array();
            foreach ($cate as $key => $value) {
                $data[$value['id']] = $value;
                $data[$value['id']]['child'] = array();
            }
            unset($cate);
        }

        // 获取二级分类下的小分类
        $list   = D('class')->getClassList(array_keys($data), array(), '');
        if (is_array($list) && count($list) > 0) {
            foreach ($list as $key => $child) {
                $data[$child['fid']]['child'][] = $child;
            }
            unset($list);
        }
        $this->ret(array_values($data));
    }


    /**
     * 数据返回
     * @param  string|data $data 返回的数据
     */
    public function ret($data) {

        if (is_array($data)) {
            $this->returnMessage(1, '成功', $data);
        } elseif (is_string($data)) {
            $this->returnMessage(0, $data, array());
        } else {
            $this->returnMessage(0, '失败', array());
        }
    }

}