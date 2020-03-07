### ph_change_ban [异动] 楼栋调整
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 3 | 流程id | process_id | tinyint(2) unsigned |  |  | NO |  |
| 4 |  | ban_id | int(10) unsigned |  |  | NO |  |
| 5 | 楼栋调整类型 | ban_change_id | tinyint(3) unsigned |  |  | NO |  |
| 6 | 原总楼层 | old_floors | tinyint(3) unsigned |  |  | NO |  |
| 7 | 变更后总楼层 | new_floors | tinyint(3) unsigned |  |  | NO |  |
| 8 | 原完损等级 | old_damage | tinyint(2) unsigned |  |  | NO |  |
| 9 | 新完损等级 | new_damage | tinyint(2) unsigned |  |  | NO |  |
| 10 |  | old_struct | tinyint(2) unsigned |  |  | NO |  |
| 11 |  | new_struct | tinyint(2) unsigned |  |  | NO |  |
| 12 | 序列化数据 | child_json | text |  |  | NO |  |
| 13 |  | data_json | text |  |  | NO |  |
| 14 | 异动备注 | change_remark | varchar(255) |  |  | NO |  |
| 15 | 变更失败原因 | change_reason | varchar(255) |  |  | NO |  |
| 16 | 异动附件 | change_imgs | varchar(255) |  |  | NO |  |
| 17 | 是否打回过 | is_back | tinyint(1) unsigned |  |  | NO |  |
| 18 | 异动状态1成功,0失败 | change_status | tinyint(1) unsigned |  |  | NO |  |
| 19 |  | entry_date | char(7) |  |  | NO |  |
| 20 |  | ctime | int(10) unsigned |  |  | NO |  |
| 21 |  | etime | timestamp |  |  | NO |  |
| 22 | 终审完成时间 | ftime | int(10) unsigned |  |  | NO |  |
| 23 |  | cuid | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
