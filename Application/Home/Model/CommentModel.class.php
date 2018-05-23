<?php 

namespace Home\Model;

use Think\Model;

/**
 * 评论模型
 */
class CommentModel extends Model {

    /**
     * 商品表名
     */
    protected $tableName = 'order_comment'; 

    /**
     * 通过商品ID 获取品论个总数
     * @param  int $goods_id 商品ID
     * @return int 商品评论条数
     */
    public function countByGoods($goods_id) {

        $count = $this->where("goods_id=$goods_id")->count();
        if (empty($count)) {
            $count = 0;
        }
        return $count;
    }
}