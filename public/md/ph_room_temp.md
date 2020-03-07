### ph_room_temp [档案] 房间
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | room_id | int(10) unsigned | PRI |  | NO |  |
| 2 |  | ban_id | int(10) unsigned |  |  | NO |  |
| 3 | 房间类型 | room_type | tinyint(1) unsigned |  |  | NO |  |
| 4 | 间号 | room_door | varchar(6) |  |  | NO |  |
| 5 | 房间编号 | room_number | varchar(6) | MUL |  | NO |  |
| 6 | 房间所在单元号 | room_unit_id | tinyint(1) unsigned |  |  | NO |  |
| 7 | 房间所在楼层号 | room_floor_id | tinyint(2) unsigned |  |  | NO |  |
| 8 | 房间规租 | room_pre_rent | decimal(6,2) unsigned |  |  | NO |  |
| 9 | 房间计算租金 | room_cou_rent | decimal(6,2) unsigned |  |  | NO |  |
| 10 | 基价折减率 | room_rent_point | decimal(3,2) unsigned |  |  | NO |  |
| 11 |  | room_rent_pointids | varchar(32) |  |  | NO |  |
| 12 | 房间建面 | room_area | decimal(5,2) unsigned |  |  | NO |  |
| 13 | 房间实有面积 | room_use_area | decimal(5,2) unsigned |  |  | NO |  |
| 14 | 房间计租面积 | room_lease_area | decimal(5,2) unsigned |  |  | NO |  |
| 15 | 几户使用 | room_pub_num | tinyint(2) unsigned |  |  | NO |  |
| 16 | 创建时间 | room_ctime | int(10) unsigned |  |  | NO |  |
| 17 | 创建人 | room_cuid | int(10) unsigned |  |  | NO |  |
| 18 | 0新发异动,1正常,2暂停计租,3注销 | room_status | tinyint(2) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
