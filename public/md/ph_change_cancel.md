### ph_change_cancel [异动] 注销
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 3 | 流程id | process_id | tinyint(2) unsigned |  |  | NO |  |
| 4 | 注销类型 | cancel_type | tinyint(1) unsigned |  |  | NO |  |
| 5 | 是否注销楼栋 | cancel_ban | tinyint(1) unsigned |  |  | NO |  |
| 6 | 注销规租 | cancel_rent | decimal(10,2) unsigned |  |  | NO |  |
| 7 | 注销建面 | cancel_area | decimal(10,2) unsigned |  |  | NO |  |
| 8 | 注销使面 | cancel_use_area | decimal(10,2) unsigned |  |  | NO |  |
| 9 | 注销原价 | cancel_oprice | decimal(10,2) unsigned |  |  | NO |  |
| 10 |  | house_id | varchar(255) |  |  | NO |  |
| 11 | 原租户id | ban_id | int(10) unsigned |  |  | NO |  |
| 12 | 新租户id | change_remark | varchar(255) |  |  | NO |  |
| 13 |  | child_json | text |  |  | NO |  |
| 14 |  | data_json | text |  |  | NO |  |
| 15 |  | change_json | text |  |  | NO |  |
| 16 | 变更原因 | change_reason | varchar(255) |  |  | NO |  |
| 17 | 异动附件 | change_imgs | varchar(255) |  |  | NO |  |
| 18 | 是否打回过 | is_back | tinyint(1) unsigned |  |  | NO |  |
| 19 | 异动状态1成功,0失败 | change_status | tinyint(1) unsigned |  |  | NO |  |
| 20 |  | entry_date | char(7) |  |  | NO |  |
| 21 |  | ctime | int(10) unsigned |  |  | NO |  |
| 22 |  | etime | timestamp |  |  | NO |  |
| 23 |  | ftime | int(10) unsigned |  |  | NO |  |
| 24 |  | cuid | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
