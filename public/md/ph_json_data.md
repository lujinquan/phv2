### ph_json_data 
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 |  | change_order_number | varchar(32) |  |  | YES |  |
| 3 |  | house_id | varchar(32) |  |  | YES |  |
| 4 |  | house_number | varchar(32) |  |  | YES |  |
| 5 |  | house_use_id | tinyint(1) unsigned |  |  | YES |  |
| 6 |  | house_pre_rent | decimal(10,2) unsigned |  |  | YES |  |
| 7 |  | house_area | decimal(10,2) |  |  | YES |  |
| 8 |  | house_oprice | decimal(10,2) unsigned |  |  | YES |  |
| 9 | 使面 | house_use_area | decimal(10,2) unsigned |  |  | YES |  |
| 10 |  | house_lease_area | decimal(10,2) unsigned |  |  | YES |  |
| 11 |  | house_pump_rent | decimal(10,2) unsigned |  |  | YES |  |
| 12 |  | house_diff_rent | decimal(10,2) unsigned |  |  | YES |  |
| 13 |  | house_protocol_rent | decimal(10,2) unsigned |  |  | YES |  |
| 14 |  | tenant_id | int(10) unsigned |  |  | YES |  |
| 15 |  | tenant_number | int(10) unsigned |  |  | YES |  |
| 16 |  | tenant_name | varchar(255) |  |  | YES |  |
| 17 |  | tenant_tel | varchar(255) |  |  | YES |  |
| 18 |  | tenant_card | varchar(255) |  |  | YES |  |
| 19 |  | changetype | varchar(255) |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
