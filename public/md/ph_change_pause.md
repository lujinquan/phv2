### ph_change_pause [异动] 暂停计租
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 3 | 流程id | process_id | tinyint(2) unsigned |  |  | NO |  |
| 4 | 多个id用逗号分隔开 | house_id | varchar(255) |  |  | NO |  |
| 5 | 原租户id | ban_id | int(10) unsigned |  |  | NO |  |
| 6 |  | child_json | text |  |  | NO |  |
| 7 | 序列化数据 | data_json | text |  |  | NO |  |
| 8 | 暂停的规租 | change_pause_rent | decimal(10,2) unsigned |  |  | NO |  |
| 9 | 备注 | change_remark | varchar(255) |  |  | NO |  |
| 10 | 变更原因 | change_reason | varchar(255) |  |  | NO |  |
| 11 | 异动附件 | change_imgs | varchar(255) |  |  | NO |  |
| 12 | 是否有效 | is_valid | tinyint(1) unsigned |  |  | NO |  |
| 13 | 是否打回过 | is_back | tinyint(1) unsigned |  |  | NO |  |
| 14 | 异动状态1成功,0失败 | change_status | tinyint(1) unsigned |  |  | NO |  |
| 15 |  | entry_date | char(7) |  |  | NO |  |
| 16 |  | ctime | int(10) |  |  | NO |  |
| 17 |  | etime | timestamp |  |  | NO |  |
| 18 | 终审完成时间 | ftime | int(10) unsigned |  |  | NO |  |
| 19 |  | cuid | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
