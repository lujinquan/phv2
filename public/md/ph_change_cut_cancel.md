### ph_change_cut_cancel [异动] 租金减免
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 租金减免异动id | change_cut_id | int(10) unsigned |  |  | NO |  |
| 3 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 4 | 流程id | process_id | tinyint(2) unsigned |  |  | NO |  |
| 5 | 租金减免类型 | cut_type | tinyint(1) unsigned |  |  | NO |  |
| 6 |  | cut_rent | decimal(10,2) unsigned |  |  | NO |  |
| 7 | 减免证件号 | cut_rent_number | varchar(255) |  |  | NO |  |
| 8 |  | ban_id | int(10) unsigned |  |  | NO |  |
| 9 |  | house_id | int(10) unsigned |  |  | NO |  |
| 10 | 原租户id | tenant_id | int(10) unsigned |  |  | NO |  |
| 11 | 序列化数据 | child_json | text |  |  | NO |  |
| 12 | 变更原因 | change_reason | varchar(255) |  |  | NO |  |
| 13 |  | change_remark | varchar(255) |  |  | NO |  |
| 14 | 异动附件 | change_imgs | varchar(255) |  |  | NO |  |
| 15 | 是否被打回过 | is_back | tinyint(1) unsigned |  |  | NO |  |
| 16 | 异动状态1成功,0失败 | change_status | tinyint(1) unsigned |  |  | NO |  |
| 17 |  | entry_date | char(7) |  |  | NO |  |
| 18 |  | ctime | int(10) unsigned |  |  | NO |  |
| 19 |  | etime | timestamp |  |  | NO |  |
| 20 |  | ftime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
