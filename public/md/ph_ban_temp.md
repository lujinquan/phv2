### ph_ban_temp [档案] 楼栋
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 | 自增主键 | ban_id | int(10) unsigned | PRI |  | NO |  |
| 2 | 楼栋编号 | ban_number | char(10) | MUL |  | NO |  |
| 3 | 民户数 | ban_civil_holds | mediumint(8) unsigned |  |  | NO |  |
| 4 | 机关户数 | ban_party_holds | mediumint(8) unsigned |  |  | NO |  |
| 5 | 企业户数 | ban_career_holds | mediumint(8) unsigned |  |  | NO |  |
| 6 | 户数 | ban_holds | mediumint(8) unsigned |  |  | NO |  |
| 7 | 栋号 | ban_door | tinyint(2) unsigned |  |  | NO |  |
| 8 | 楼栋的单元数量 | ban_units | tinyint(2) unsigned |  |  | NO |  |
| 9 | 楼栋的楼层数量 | ban_floors | tinyint(3) unsigned |  |  | NO |  |
| 10 | 街道代号 | ban_street | char(4) |  |  | NO |  |
| 11 | 社区代号 | ban_unity | char(4) |  |  | NO |  |
| 12 | 楼栋地址 | ban_address | varchar(255) |  |  | NO |  |
| 13 | 所id | ban_inst_pid | tinyint(2) unsigned |  |  | NO |  |
| 14 | 管段id | ban_inst_id | tinyint(2) unsigned |  |  | NO |  |
| 15 | 产别 | ban_owner_id | tinyint(1) unsigned |  |  | NO |  |
| 16 | 产权证号 | ban_property_id | varchar(255) |  |  | NO |  |
| 17 | 土地证号 | ban_land_id | varchar(255) |  |  | NO |  |
| 18 | 产权来源 | ban_property_source | varchar(255) |  |  | NO |  |
| 19 | 楼栋建成年份 | ban_build_year | mediumint(4) unsigned |  |  | NO |  |
| 20 | 栋系数 | ban_ratio | decimal(6,3) unsigned |  |  | NO |  |
| 21 | 完损等级id | ban_damage_id | tinyint(1) unsigned |  |  | NO |  |
| 22 | 结构类别id | ban_struct_id | tinyint(1) unsigned |  |  | NO |  |
| 23 | 民建面 | ban_civil_area | decimal(10,2) unsigned |  |  | NO |  |
| 24 | 机关建面 | ban_party_area | decimal(10,2) unsigned |  |  | NO |  |
| 25 | 企业建面 | ban_career_area | decimal(10,2) unsigned |  |  | NO |  |
| 26 | 民栋数 | ban_civil_num | tinyint(1) unsigned |  |  | NO |  |
| 27 | 机栋数 | ban_party_num | tinyint(1) unsigned |  |  | NO |  |
| 28 | 企栋数 | ban_career_num | tinyint(1) unsigned |  |  | NO |  |
| 29 | 民规租 | ban_civil_rent | decimal(10,2) unsigned |  |  | NO |  |
| 30 | 机规租 | ban_party_rent | decimal(10,2) unsigned |  |  | NO |  |
| 31 | 企规租 | ban_career_rent | decimal(10,2) unsigned |  |  | NO |  |
| 32 | 民原价 | ban_civil_oprice | decimal(10,2) unsigned |  |  | NO |  |
| 33 | 机原价 | ban_party_oprice | decimal(10,2) unsigned |  |  | NO |  |
| 34 | 企原价 | ban_career_oprice | decimal(10,2) |  |  | NO |  |
| 35 | 实有面积 | ban_use_area | decimal(10,2) unsigned |  |  | NO |  |
| 36 | 占地面积 | ban_cover_area | decimal(10,2) unsigned |  |  | NO |  |
| 37 | 证载面积 | ban_actual_area | decimal(10,2) unsigned |  |  | NO |  |
| 38 | 经度 | ban_gpsx | varchar(64) |  |  | NO |  |
| 39 | 纬度 | ban_gpsy | varchar(64) |  |  | NO |  |
| 40 | 楼栋附件 | ban_imgs | varchar(255) |  |  | NO |  |
| 41 | 一层是否为架空层 | ban_is_first | tinyint(1) unsigned |  |  | NO |  |
| 42 | 是否有电梯 | ban_is_levator | tinyint(1) unsigned |  |  | NO |  |
| 43 | 创建时间 | ban_ctime | int(10) unsigned |  |  | NO |  |
| 44 | 创建人id | ban_cuid | int(10) unsigned |  |  | NO |  |
| 45 | 楼栋状态 ,0新发异动中,1正常,2注销 | ban_status | tinyint(2) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
