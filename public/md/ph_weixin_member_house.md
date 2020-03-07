### ph_weixin_member_house [用户版小程序] 会员关联房屋
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 房屋id | house_id | int(10) unsigned |  |  | NO |  |
| 3 | 微信会员id | member_id | int(11) unsigned |  |  | NO |  |
| 4 | 房屋是否认证 | is_auth | tinyint(1) unsigned |  |  | NO |  |
| 5 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 6 | 删除时间 | dtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | gbk_chinese_ci | gbk_chinese_ci |gbk_chinese_ci |
