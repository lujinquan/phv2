### ph_weixin_column [用户端小程序] 主页菜单
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 | 用户版小程序主页栏目类别id | col_id | int(10) unsigned | PRI |  | NO |  |
| 2 | 栏目名称 | col_name | varchar(32) |  |  | NO |  |
| 3 | 栏目icon链接 | col_icon | varchar(255) |  |  | NO |  |
| 4 | 对应的小程序页面 | app_page | varchar(255) |  |  | NO |  |
| 5 | 栏目排序，越大越靠前 | sort | int(11) unsigned |  |  | NO |  |
| 6 | 菜单是否显示 | is_show | tinyint(1) unsigned |  |  | NO |  |
| 7 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 8 | 删除时间 | dtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | gbk_chinese_ci | gbk_chinese_ci |gbk_chinese_ci |
