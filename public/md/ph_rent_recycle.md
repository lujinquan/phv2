### ph_rent_recycle [租金] 年度月度收欠表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 |  | house_id | varchar(32) |  |  | NO |  |
| 3 |  | tenant_id | int(10) unsigned |  |  | NO |  |
| 4 |  | tenant_name | varchar(255) |  |  | NO |  |
| 5 |  | pay_rent | decimal(10,2) unsigned |  |  | NO |  |
| 6 | 收回单子的年份 | pay_year | int(4) unsigned |  |  | NO |  |
| 7 | 收回单子的月份 | pay_month | varchar(255) |  |  | NO |  |
| 8 |  | cdate | int(6) unsigned |  |  | NO |  |
| 9 |  | ctime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
