### ph_change_name [异动] 别字更正
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 3 | 流程id | process_id | tinyint(2) unsigned |  |  | NO |  |
| 4 |  | ban_id | int(10) unsigned |  |  | NO |  |
| 5 |  | house_id | varchar(32) |  |  | NO |  |
| 6 | 原租户id | tenant_id | int(10) unsigned |  |  | NO |  |
| 7 |  | old_tenant_name | varchar(64) |  |  | NO |  |
| 8 |  | new_tenant_name | varchar(64) |  |  | NO |  |
| 9 | 序列化数据 | child_json | text |  |  | NO |  |
| 10 | 异动备注 | change_remark | varchar(255) |  |  | NO |  |
| 11 | 变更失败原因 | change_reason | varchar(255) |  |  | NO |  |
| 12 | 异动附件 | change_imgs | varchar(255) |  |  | NO |  |
| 13 | 是否打回过 | is_back | tinyint(1) unsigned |  |  | NO |  |
| 14 | 异动状态1成功,0失败 | change_status | tinyint(1) unsigned |  |  | NO |  |
| 15 |  | entry_date | char(7) |  |  | NO |  |
| 16 |  | ctime | int(10) unsigned |  |  | NO |  |
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
