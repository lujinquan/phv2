### ph_weixin_order 订单总表，支付时候使用
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 | 自增id， | order_id | int(10) | PRI |  | NO |  |
| 2 | 会员id | member_id | int(10) unsigned | MUL |  | NO |  |
| 3 | 支付金额 | pay_money | float(10,2) |  |  | YES |  |
| 4 | 租金订单id | rent_order_id | int(10) unsigned |  |  | NO |  |
| 5 | 微信交易号 | transaction_id | varchar(255) |  |  | YES |  |
| 6 | 提交给微信的订单号 | out_trade_no | varchar(100) |  |  | YES |  |
| 7 | 支付时间 | ptime | int(10) unsigned |  |  | NO |  |
| 8 | 添加时间 | ctime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
