### ph_floor_point [配置] 层次调解率
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | floor_id | int(10) unsigned | PRI |  | NO |  |
| 2 | 总楼层数 | floor_total | int(3) unsigned |  |  | NO |  |
| 3 | 居住层 | floor_live | int(3) unsigned |  |  | NO |  |
| 4 | 层次调解率 | floor_point | decimal(4,2) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
