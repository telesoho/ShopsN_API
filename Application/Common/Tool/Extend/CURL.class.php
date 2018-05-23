<?php
namespace Common\Tool\Extend;


use Common\Tool\Tool;

class CURL extends Tool
{
    /**
     * @param array  $file 文件信息
     * @param string $url  上传的URL
     */
    public function uploadFile(array $file, $url, $userId, $header = null)
    {
        if (empty($file) || empty($url) || empty($url) || !is_numeric($userId))
        {
            throw new \Exception('文件错误');
        }
        //php 5.5以上的用法
        if (class_exists('\CURLFile')) {
            $data = array(
                'file' => new \CURLFile(realpath($file['tmp_name']),$file['type'],$file['name']),
                'user_id'   => $userId
            );
        } else {
            $data = array(
                'file'          =>'@'.realpath($file['tmp_name']).";type=".$file['type'].";filename=".$file['name'],
                'user_id'       => $userId,
                'user_header'   => $header
            );
        }
        $returnData = $this->curlConfig($url, $data);
        return $returnData;
    }
    
    
    private function curlConfig($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $returnData = curl_exec($ch);
        curl_close($ch);
        return $returnData;
    }
    
    /**
     * 删除文件 
     */
    public function deleteFile(array $file, $url)
    {
        if (empty($file) || empty($url))
        {
            return false;
        }
        return $this->curlConfig($url, $file);
    }
}