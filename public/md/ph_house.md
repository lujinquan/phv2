### ph_house [档案] 房屋
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 | 房屋id | house_id | int(10) unsigned | PRI |  | NO |  |
| 2 | 租约编号前缀 | house_szno | varchar(32) |  |  | NO |  |
| 3 | 房屋编号 | house_number | char(14) | MUL |  | NO |  |
| 4 | 房屋规定租金 | house_pre_rent | decimal(8,2) unsigned |  |  | NO |  |
| 5 | 房屋计算租金 | house_cou_rent | decimal(8,2) unsigned |  |  | NO |  |
| 6 |  | ban_id | int(10) unsigned | MUL |  | NO |  |
| 7 |  | tenant_id | int(10) unsigned | MUL |  | NO |  |
| 8 | 居住单元号 | house_unit_id | tinyint(2) unsigned |  |  | NO |  |
| 9 | 居住楼层号 | house_floor_id | tinyint(2) unsigned |  |  | NO |  |
| 10 | 门牌号 | house_door | varchar(32) |  |  | NO |  |
| 11 | 使用性质 | house_use_id | tinyint(1) unsigned |  |  | NO |  |
| 12 |  | house_imgs | varchar(255) |  |  | NO |  |
| 13 | 5米以下的房间个数 | house_below_five_num | tinyint(3) unsigned |  |  | NO |  |
| 14 | 5米以上房间个数 | house_more_five_num | tinyint(3) unsigned |  |  | NO |  |
| 15 | 房屋建筑面积 | house_area | decimal(8,2) unsigned |  |  | NO |  |
| 16 | 房屋实有面积 | house_use_area | decimal(6,2) unsigned |  |  | NO |  |
| 17 | 房屋计租面积 | house_lease_area | decimal(10,2) unsigned |  |  | NO |  |
| 18 | 房屋原价 | house_oprice | decimal(10,2) unsigned |  |  | NO |  |
| 19 | 房屋泵费 | house_pump_rent | decimal(10,2) unsigned |  |  | NO |  |
| 20 | 房屋租差 | house_diff_rent | decimal(10,2) unsigned |  |  | NO |  |
| 21 | 协议租金 | house_protocol_rent | decimal(10,2) unsigned |  |  | NO |  |
| 22 | 账户余额 | house_balance | decimal(10,2) unsigned |  |  | NO |  |
| 23 |  | house_ctime | int(10) unsigned |  |  | NO |  |
| 24 | 创建人 | house_cuid | int(10) unsigned |  |  | NO |  |
| 25 | 是否暂停计租 | house_is_pause | tinyint(1) unsigned |  |  | NO |  |
| 26 | 状态，0未确认，1正常，2暂停计租，3注销 | house_status | tinyint(2) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
