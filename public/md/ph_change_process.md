### ph_change_process [异动] 审批临时表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 |  | change_id | int(11) unsigned |  |  | NO |  |
| 3 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 4 | 异动类型 | change_type | tinyint(2) unsigned |  |  | NO |  |
| 5 | 楼栋id | ban_id | varchar(10) |  |  | NO |  |
| 6 | 新租户id | change_remark | varchar(255) |  |  | NO |  |
| 7 | 异动状态描述 | change_desc | varchar(100) |  |  | NO |  |
| 8 | 当前审核的角色id | curr_role | int(10) unsigned |  |  | NO |  |
| 9 | 当前异动打印次数,目前只用于租约管理 | print_times | int(10) unsigned |  |  | NO |  |
| 10 |  | ctime | int(10) unsigned |  |  | NO |  |
| 11 |  | etime | timestamp |  |  | NO |  |
| 12 |  | ftime | int(10) unsigned |  |  | NO |  |
| 13 |  | cuid | int(10) unsigned |  |  | NO |  |
| 14 | 状态 | status | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
