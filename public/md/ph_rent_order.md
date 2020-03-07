### ph_rent_order [租金] 月租金订单
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | rent_order_id | int(10) unsigned | PRI |  | NO |  |
| 2 | 订单编号 | rent_order_number | varchar(32) |  |  | NO |  |
| 3 | 订单日期 | rent_order_date | char(6) |  |  | NO |  |
| 4 | 减免租金 | rent_order_cut | decimal(10,2) unsigned |  |  | NO |  |
| 5 | 租差 | rent_order_diff | decimal(10,2) unsigned |  |  | NO |  |
| 6 | 泵费 | rent_order_pump | decimal(10,2) unsigned |  |  | NO |  |
| 7 |  | rent_order_pre_rent | decimal(10,2) |  |  | NO |  |
| 8 |  | rent_order_cou_rent | decimal(10,2) |  |  | NO |  |
| 9 | 应收租金 | rent_order_receive | decimal(10,2) unsigned |  |  | NO |  |
| 10 | 已缴租金 | rent_order_paid | decimal(10,2) unsigned |  |  | NO |  |
| 11 |  | rent_order_remark | varchar(255) |  |  | NO |  |
| 12 |  | house_id | varchar(32) | MUL |  | NO |  |
| 13 |  | tenant_id | int(10) unsigned | MUL |  | NO |  |
| 14 | 支付方式 | pay_way | tinyint(1) unsigned |  |  | NO |  |
| 15 | 是否处理过该订单 | is_deal | tinyint(1) unsigned |  |  | NO |  |
| 16 |  | ctime | int(10) unsigned |  |  | NO |  |
| 17 | 支付时间 | ptime | int(10) unsigned |  |  | NO |  |
| 18 | 是否已开发票 | is_invoice | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
