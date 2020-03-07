### ph_weixin_guide [用户版小程序] 办事指引
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 是否置顶1置顶2不置顶 | is_top | tinyint(1) unsigned |  |  | NO |  |
| 3 | 排序 | sort | int(10) unsigned |  |  | NO |  |
| 4 |  | title | varchar(255) |  |  | NO |  |
| 5 |  | content | text |  |  | NO |  |
| 6 | 简介 | remark | varchar(255) |  |  | NO |  |
| 7 | 创建人 | cuid | int(10) unsigned |  |  | NO |  |
| 8 |  | ctime | int(10) unsigned |  |  | NO |  |
| 9 |  | etime | int(10) unsigned |  |  | NO |  |
| 10 |  | dtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
