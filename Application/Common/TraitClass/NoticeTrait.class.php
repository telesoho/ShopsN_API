<?php
namespace Common\TraitClass;

trait NoticeTrait 
{

    /**
     * 提示client
     * 
     * @param array $data
     *            要检测的数据
     * @param string $checkKey
     *            要检测的键
     * @param string $message
     *            信息
     * @param bool $isValidate
     *            是否检测建
     */
    protected function prompt ($data, $url = '', $checkKey = null, $message = '暂无数据，请添加', 
            $isValidate = FALSE)
    {
        if (empty($data)) {
            $this->error($message, $url);
        } elseif (is_array($data) && empty($data[$checkKey]) && $isValidate) {
            $this->error($message, $url);
        }
        return true;
    }

    protected function isSucess ($status, $url, $message = '添加成功') {

        if (empty($status)) {
            $this->error($message);
        } else {
            $this->success($message, $url);
        }
    }
    
    /**
     * 提示client
     * 
     * @param array $data
     *            要检测的数据
     * @param string $checkKey
     *            要检测的键
     * @param string $message
     *            信息
     * @param bool $isValidate
     *            是否检测建
     */
    protected function promptPjax ($data, $message = '暂无数据，请添加', $checkKey = null, 
            $isValidate = FALSE)
    {
        if (empty($data)) {
            $this->ajaxReturnData(null, 0, $message);
        } elseif (is_array($data) && empty($data[$checkKey]) && $isValidate) {
            $this->ajaxReturnData(null, 0, $message);
        }
        return true;
    }

    protected function alreadyInData ($data, $message = '已存在该数据')
    {
        if (! empty($data)) {
            $this->error($message);
        }
        return true;
    }

    protected function alreadyInDataPjax ($data, $message = '已存在该数据')
    {
        if (! empty($data)) {
            $this->ajaxReturnData(null, 0, $message);
        }
        return true;
    }
    
    /**
     * ajax 返回数据
     */
    protected function ajaxReturnData($data, $status= 1, $message = '操作成功')
    {
        $this->ajaxReturn(array(
                'status'  => $status,
                'msg' => $message,
                'data'    => $data
        ));
        die();
    }
    
    protected function updateClient($insert_id, $message)
    {
        $status    = empty($insert_id) ? 0 : 1;
        $message   = empty($insert_id) ? $message.'，失败' : $message.'，成功';
        $this->ajaxReturnData($insert_id, $status, $message);
    }
    
    protected function addClient($insert_id)
    {
        $status    = empty($insert_id) ? 0 : 1;
        $message   = empty($insert_id) ? '添加失败' : '添加成功';
        $this->ajaxReturnData($insert_id, $status, $message);
    }
    
    /**
     * 判断数字编号
     * @param int $id 数字编号
     */
    protected function errorNotice($id)
    {
        if (empty($id) || !is_numeric($id) || $id == 0) {
            $this->error('灌水机制已打开');
        }
        return true;
    }
}