### ph_op_order [工单]工单表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 工单编号 | op_order_number | varchar(32) |  |  | NO |  |
| 3 | 工单类型 | op_order_type | tinyint(2) unsigned |  |  | NO |  |
| 4 | 关键编号 | key_number | tinytext |  |  | NO |  |
| 5 | 附件 | imgs | varchar(64) |  |  | NO |  |
| 6 | 推至发起人的次数 | back_times | tinyint(1) unsigned |  |  | NO |  |
| 7 | 发起人 | cuid | tinyint(2) unsigned |  |  | NO |  |
| 8 |  | inst_id | tinyint(2) unsigned |  |  | NO |  |
| 9 | 经手人员记录 | duid | varchar(32) |  |  | NO |  |
| 10 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 11 | 运行中心完结工单的时间 | dtime | int(10) unsigned |  |  | NO |  |
| 12 | 房管员确认时间 | ftime | int(10) unsigned |  |  | NO |  |
| 13 | 序列化数据 | jsondata | text |  |  | NO |  |
| 14 | 问题描述 | remark | tinytext |  |  | NO |  |
| 15 | 状态 | status | tinyint(2) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
