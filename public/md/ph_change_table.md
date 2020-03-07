### ph_change_table [异动] 统计表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 |  | change_type | tinyint(3) unsigned |  |  | NO |  |
| 3 |  | change_order_number | varchar(32) |  |  | NO |  |
| 4 | 新发类型 | change_send_type | tinyint(1) unsigned |  |  | NO |  |
| 5 | 注销类型 | change_cancel_type | tinyint(1) unsigned |  |  | NO |  |
| 6 |  | inst_pid | tinyint(2) unsigned |  |  | NO |  |
| 7 |  | inst_id | tinyint(2) unsigned |  |  | NO |  |
| 8 | 新管段 | new_inst_id | tinyint(2) unsigned |  |  | NO |  |
| 9 | 产别 | owner_id | tinyint(2) unsigned |  |  | NO |  |
| 10 | 使用性质id | use_id | tinyint(2) unsigned |  |  | NO |  |
| 11 | 减免类型 | cut_type | tinyint(1) unsigned |  |  | NO |  |
| 12 | 租金变化 | change_rent | decimal(10,2) |  |  | NO |  |
| 13 | 建面变化 | change_area | decimal(10,2) |  |  | NO |  |
| 14 | 使面变化 | change_use_area | decimal(10,2) |  |  | NO |  |
| 15 | 原价变化 | change_oprice | decimal(10,2) |  |  | NO |  |
| 16 | 栋数变化 | change_ban_num | tinyint(2) |  |  | NO |  |
| 17 | 以前月租金 | change_month_rent | decimal(10,2) |  |  | NO |  |
| 18 | 以前年租金 | change_year_rent | decimal(10,2) |  |  | NO |  |
| 19 |  | tenant_id | int(10) unsigned |  |  | NO |  |
| 20 |  | house_id | varchar(32) |  |  | NO |  |
| 21 |  | ban_id | int(10) unsigned |  |  | NO |  |
| 22 |  | end_date | mediumint(8) unsigned |  |  | NO |  |
| 23 |  | order_date | mediumint(8) unsigned |  |  | NO |  |
| 24 | 是否有效 | change_status | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
