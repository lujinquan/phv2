### ph_weixin_banner [用户版小程序] 轮播图
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 轮播图标题 | banner_title | varchar(64) |  |  | NO |  |
| 3 | 轮播图url | banner_img | varchar(255) |  |  | NO |  |
| 4 | 轮播图的链接类型 | banner_url_type | tinyint(1) unsigned |  |  | NO |  |
| 5 | 轮播图跳转链接 | banner_url | varchar(255) |  |  | NO |  |
| 6 | 轮播图链接类型为3，必填的外部小程序的appid | ext_appid | varchar(255) |  |  | NO |  |
| 7 | 轮播图排序，越大越靠前 | sort | int(10) unsigned |  |  | NO |  |
| 8 | 是否显示 | is_show | tinyint(1) unsigned |  |  | NO |  |
| 9 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 10 | 删除时间 | dtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
