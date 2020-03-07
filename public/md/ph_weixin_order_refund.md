### ph_weixin_order_refund 用户退款表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 | 退款自增id | ref_id | int(10) | PRI |  | NO |  |
| 2 | 订单id | order_id | int(10) |  |  | NO |  |
| 3 | 退款金额 | ref_money | float(10,2) |  |  | NO |  |
| 4 | 会员id | member_id | int(10) | MUL |  | NO |  |
| 5 | 退款原因 | ref_name | varchar(255) |  |  | NO |  |
| 6 | 联系人手机号 | ref_mobile | varchar(50) |  |  | NO |  |
| 7 | 联系人姓名 | complaint_name | varchar(255) |  |  | YES |  |
| 8 | 问题描述 | ref_description | text |  |  | YES |  |
| 9 | 退款是否成功：1成功，0失败 | ref_status | tinyint(1) |  |  | NO |  |
| 10 | 管理员备注 | ref_remark | varchar(255) |  |  | YES |  |
| 11 | 申请时间 | ctime | int(10) |  |  | NO |  |
| 12 | 退款订单管理员修改时间 | mtime | int(10) |  |  | YES |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
