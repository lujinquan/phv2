<?php

/**
 * 1、同步楼栋表[ph_ban => ph_ban_back]
 * 字段：楼栋编号、栋号、单元数量、楼层数量、楼栋地址、管段、所、产别、产权、产权来源、建成年份、栋系数、完损等级、结构类别
 * 民建面、机建面、企建面、民栋数、机栋数、企栋数、民规租、机规租、企规租、民原价、机原价、企原价、使面、状态
 */
drop table if exists phv2.ph_ban_back;
create table phv2.ph_ban_back like phv2.ph_ban;
# 同步数据
insert into phv2.ph_ban_back 
(ban_number,ban_door,ban_units,ban_floors,ban_address,ban_inst_id,ban_inst_pid,ban_owner_id,ban_property_id,ban_property_source,ban_build_year,ban_ratio,ban_damage_id,ban_struct_id,ban_civil_area,ban_party_area,ban_career_area,ban_civil_num,ban_party_num,ban_career_num,ban_civil_rent,ban_party_rent,ban_career_rent,ban_civil_oprice,ban_party_oprice,ban_career_oprice,ban_use_area,ban_status) 
select 
BanID,BanNumber,BanUnitNum,BanFloorNum,AreaFour,TubulationID,InstitutionID,OwnerType,BanPropertyID,PropertySource,BanYear,BanRatio,DamageGrade,StructureType,CivilArea,PartyArea,EnterpriseArea,CivilNum,PartyNum,EnterpriseNum,CivilRent,PartyRent,EnterpriseRent,CivilOprice,PartyOprice,EnterpriseOprice,BanUsearea,Status
from phv1.ph_ban;



/**
 * 2、将v1的房屋表中的ban_number全部替换成v2楼栋表中的ban_id [反向更新v1的房屋表]
 */
# update phv1.ph_house as a inner join phv2.ph_ban_back as b on a.BanID = b.ban_number set a.BanID = b.ban_id;
update phv1.ph_house a,phv2.ph_ban_back b set a.BanID = b.ban_id where a.BanID = b.ban_number;



/**
 * 3、同步租户表[ph_tenant => ph_tenant_back]
 * 字段：租户编号、租户姓名、管段、所、电话、身份证号、状态
 */
drop table if exists phv2.ph_tenant_back;
create table phv2.ph_tenant_back like phv2.ph_tenant;
# 同步数据
insert into phv2.ph_tenant_back 
(tenant_number,tenant_name,tenant_inst_id,tenant_inst_pid,tenant_tel,tenant_card,tenant_status) 
select 
TenantID,TenantName,InstitutionID,InstitutionPID,TenantTel,TenantNumber,Status
from phv1.ph_tenant;



/**
 * 4、将v1的房屋表中的tenant_number全部替换成v2楼栋表中的tenant_id [反向更新v1的房屋表]
 */
# update phv1.ph_house as a left join phv2.ph_tenant_back as b on a.TenantID = b.tenant_number set a.TenantID = b.tenant_id;
update phv1.ph_house a,phv2.ph_tenant_back b set a.TenantID = b.tenant_id where a.TenantID = b.tenant_number;



/**
 * 5、同步房屋表[ph_house => ph_house_back]
 * 字段：房屋编号、规租、计算租金、单元号、楼层号、门牌号、使用性质、使用面积、建面、计租面积、原价、泵费、租差、协议租金、状态
 */
drop table if exists phv2.ph_house_back;
create table phv2.ph_house_back like phv2.ph_house;
# 同步数据
insert into phv2.ph_house_back 
(house_number,ban_id,tenant_id,house_pre_rent,house_cou_rent,house_unit_id,house_floor_id,house_door,house_use_id,house_use_area,house_area,house_lease_area,house_oprice,house_pump_rent,house_diff_rent,house_protocol_rent,house_status) 
select 
HouseID,BanID,TenantID,HousePrerent,ApprovedRent,UnitID,FloorID,DoorID,UseNature,HouseUsearea,HouseArea,LeasedArea,OldOprice,PumpCost,DiffRent,ProtocolRent,Status
from phv1.ph_house;



/**
 * 6、将v1的房间表中的ban_number全部替换成v2房间中的ban_id [反向更新v1的房屋表]
 */
update phv1.ph_room a,phv2.ph_ban_back b set a.BanID = b.ban_id where a.BanID = b.ban_number;



/**
 * 7、同步房间表[ph_room => ph_room_back]
 * 字段：房间编号、房间类型、规租、计算租金、间号、单元号、楼层号、使用面积、建面、计租面积、状态
 */
drop table if exists phv2.ph_room_back;
create table phv2.ph_room_back like phv2.ph_room;
# 同步数据
insert into phv2.ph_room_back 
(room_number,ban_id,room_type,room_pre_rent,room_cou_rent,room_door,room_unit_id,room_floor_id,room_use_area,room_area,room_lease_area,room_status) 
select 
RoomID,BanID,RoomType,RoomPrerent,RoomRentMonth,RoomNumber,UnitID,FloorID,UseArea,RoomArea,LeasedArea,Status
from phv1.ph_room;


/**
 * 8、同步由逗号分隔的数据到test表中
 */
truncate table phv2.test;
insert into phv2.test 
(a,b) 
select 
RoomID,HouseID
from phv1.ph_room;



/**
 * 9、运行存储过程，将逗号分隔的数据拆分到test1表中 耗时262.971s
 */
# 运行存储过程函数（将有逗号分隔的插入到test1表中）
truncate table phv2.test1;
call split_str();
# 将无逗号分隔的插入到test1表中
insert into phv2.test1 (select * from phv2.test where locate(',',b) = 0);



/**
 * 10、同步房屋房间映射表[test1 => ph_house_room_back]
 */
drop table if exists phv2.ph_house_room_back;
create table phv2.ph_house_room_back like phv2.ph_house_room;
# 同步数据
insert into phv2.ph_house_room_back 
(room_number,house_number) 
select 
a,b
from phv2.test1;



/**
 * 11、补充house_room_back表
 */
update phv2.ph_house_room_back a,phv2.ph_room_back b set a.room_id = b.room_id where a.room_number = b.room_number;
update phv2.ph_house_room_back a,phv2.ph_house_back b set a.house_id = b.house_id where a.house_number = b.house_number;


/* 至此，档案数据全部同步完成 */




/**
 * 12、同步租金表[ph_rent_order => ph_rent_order]
 * 字段：订单编号、订单日期、减免租金、租差、泵费、应收租金、已缴租金、房屋编号、租户编号、支付时间（用创建时间作为支付时间）
 */
drop table if exists phv2.ph_rent_order_back;
create table phv2.ph_rent_order_back like phv2.ph_rent_order;
# 同步数据
insert into phv2.ph_rent_order_back 
(rent_order_number,rent_order_date,rent_order_cut,rent_order_diff,rent_order_pump,rent_order_receive,rent_order_paid,house_id,tenant_id,ptime) 
select 
RentOrderID,OrderDate,CutRent,DiffRent,PumpCost,ReceiveRent,PaidRent,HouseID,TenantID,CreateTime
from phv1.ph_rent_order;



/**
 * 13、补充rent_order_back表 41s
 */
update phv2.ph_rent_order_back a,phv2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update phv2.ph_rent_order_back a,phv2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update phv2.ph_rent_order_back set ptime = 0 where rent_order_receive > rent_order_paid;
update phv2.ph_rent_order_back set is_deal = 1 where rent_order_paid > 0;



/**
 * 14、同步租金收欠表[ph_old_rent => ph_rent_recycle]
 * 字段：房屋编号、租户编号、租户姓名、支付金额、订单年份、订单月份、创建月份、创建时间
 */
drop table if exists phv2.ph_rent_recycle_back;
create table phv2.ph_rent_recycle_back like phv2.ph_rent_recycle;
# 同步数据
insert into phv2.ph_rent_recycle_back 
(house_id,tenant_id,tenant_name,pay_rent,pay_year,pay_month,cdate,ctime) 
select 
HouseID,TenantID,TenantName,PayRent,PayYear,PayMonth,OldPayMonth,CreateTime
from phv1.ph_old_rent;



/**
 * 15、补充ph_rent_recycle表 41s
 */
update phv2.ph_rent_recycle_back a,phv2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update phv2.ph_rent_recycle_back a,phv2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;



/**
 * 16、同步租约表[ph_lease_change_order => ph_change_lease]
 * 字段：异动编号、流程id、房屋编号、备注、租户编号、租户姓名、子流程过程、序列化数据、打印次数、最近打印时间、有效二维码url、租约编号、失败原因、附件、状态、创建时间
 */
drop table if exists phv2.ph_change_lease_back;
create table phv2.ph_change_lease_back like phv2.ph_change_lease;
# 同步数据
insert into phv2.ph_change_lease_back 
(change_order_number,process_id,house_id,change_remark,tenant_id,tenant_name,change_child_line,change_data_line,last_print_time,print_times,qrcode,szno,reason,change_imgs,change_status,ctime) 
select 
ChangeOrderID,ProcessConfigType,HouseID,Recorde,TenantID,TenantName,Child,Deadline,PrintTime,PrintTimes,QrcodeUrl,Szno,Reason,ChangeImageIDS,Status,CreateTime
from phv1.ph_lease_change_order;



/**
 * 17、补充ph_change_lease表 
 */
update phv2.ph_change_lease_back a,phv2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update phv2.ph_change_lease_back a,phv2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;



/**
 * 18、同步异动统计表[ph_rent_table => ph_change_table]
 * 字段：异动编号、异动类型、新发类型、注销类型、所id、管段、产别、使用性质、影响租金、影响建面、影响使面、影响原价、影响栋数、以前月租金、以前年租金、租户编号、房屋编号、楼栋编号、异动日期、状态
 */
drop table if exists phv2.ph_change_table_back;
create table phv2.ph_change_table_back like phv2.ph_change_table;
# 同步数据
insert into phv2.ph_change_table_back 
(change_order_number,change_type,change_send_type,change_cancel_type,inst_pid,inst_id,new_inst_id,owner_id,use_id,change_rent,change_area,change_use_area,change_oprice,change_ban_num,change_month_rent,change_year_rent,tenant_id,house_id,ban_id,order_date,change_status) 
select 
ChangeOrderID,ChangeType,NewSendRentType,CancelType,InstitutionPID,InstitutionID,NewInstitutionID,OwnerType,UseNature,InflRent,Area,UseArea,Oprice,ChangeNum,OldMonthRent,OldYearRent,TenantID,HouseID,BanID,OrderDate,Status
from phv1.ph_rent_table;



/**
 * 17、补充ph_change_table表 
 */
update phv2.ph_change_table_back a,phv2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update phv2.ph_change_table_back a,phv2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update phv2.ph_change_table_back a,phv2.ph_ban_back b set a.ban_id = b.ban_id where a.ban_id = b.ban_number;



/**
 * 19、同步使用权变更表[ph_use_change_order => ph_change_use]
 * 字段：异动编号、变更类型、转让金额、房屋编号、原租户编号、原租户姓名、新租户编号、新租户姓名、备注、失败原因、附件、创建时间、状态
 */
drop table if exists phv2.ph_change_use_back;
create table phv2.ph_change_use_back like phv2.ph_change_use;
# 同步数据
insert into phv2.ph_change_use_back 
(change_order_number,change_use_type,transfer_rent,house_id,old_tenant_id,old_tenant_name,new_tenant_id,new_tenant_name,change_remark,change_reason,change_imgs,ctime,cuid,change_status) 
select 
ChangeOrderID,ChangeType,TransferRent,HouseID,OldTenantID,OldTenantName,NewTenantID,NewTenantName,ChangeReason,Reson,ChangeImageIDS,CreateTime,UserNumber,Status
from phv1.ph_use_change_order;


/**
 * 20、补充ph_change_use表 
 */
update phv2.ph_change_use_back a,phv2.ph_tenant_back b set a.old_tenant_id = b.tenant_id where a.old_tenant_id = b.tenant_number;
update phv2.ph_change_use_back a,phv2.ph_tenant_back b set a.new_tenant_id = b.tenant_id where a.new_tenant_id = b.tenant_number;
update phv2.ph_change_use_back a,phv2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update phv2.ph_change_use_back a,phv2.ph_system_user b set a.cuid = b.id where a.cuid = b.number;


/*CREATE FUNCTION str_for_substr (num int, str varchar(50000)) RETURNS VARCHAR (100)
BEGIN
	RETURN (
		SUBSTRING(
			SUBSTRING_INDEX(str, ',', num + 1),
			CASE num
		WHEN 0 THEN
			CHAR_LENGTH(
				SUBSTRING_INDEX(str, ',', num)
			) + 1
		ELSE
			CHAR_LENGTH(
				SUBSTRING_INDEX(str, ',', num)
			) + 2
		END,
		CASE num
	WHEN 0 THEN
		CHAR_LENGTH(
			SUBSTRING_INDEX(str, ',', num + 1)
		) - CHAR_LENGTH(
			SUBSTRING_INDEX(str, ',', num)
		)
	ELSE
		CHAR_LENGTH(
			SUBSTRING_INDEX(str, ',', num + 1)
		) - CHAR_LENGTH(
			SUBSTRING_INDEX(str, ',', num)
		) - 1
	END
		)
	)
END

delimiter $$
CREATE FUNCTION str_for_substr (num int, str varchar(50000)) RETURNS VARCHAR (100)
BEGIN
	RETURN (
		SUBSTRING(
			SUBSTRING_INDEX(str, ',', num + 1),
			CASE num
		WHEN 0 THEN
			CHAR_LENGTH(
				SUBSTRING_INDEX(str, ',', num)
			) + 1
		ELSE
			CHAR_LENGTH(
				SUBSTRING_INDEX(str, ',', num)
			) + 2
		END,
		CASE num
	WHEN 0 THEN
		CHAR_LENGTH(
			SUBSTRING_INDEX(str, ',', num + 1)
		) - CHAR_LENGTH(
			SUBSTRING_INDEX(str, ',', num)
		)
	ELSE
		CHAR_LENGTH(
			SUBSTRING_INDEX(str, ',', num + 1)
		) - CHAR_LENGTH(
			SUBSTRING_INDEX(str, ',', num)
		) - 1
	END
		)
	);
END$$
delimiter ;*/

/*BEGIN
	DECLARE a1 varCHAR(20);
  DECLARE b1 varchar(10000);
  
  DECLARE done INT DEFAULT FALSE;

  
  DECLARE cur CURSOR FOR SELECT a,b from test ;
  
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  
  OPEN cur;
  
  
  read_loop: LOOP
    
    FETCH cur INTO a1,b1;

    
    IF done THEN
      LEAVE read_loop;
    END IF;

   SET @num = LENGTH(b1) - LENGTH(REPLACE(b1, ',', ''));


SET @i = 0;


WHILE (@i <=@num ) DO
    INSERT INTO test1
VALUES
    (
        a1,
      str_for_substr(@i,b1)
);

set @i = @i+1;

END WHILE;

  END LOOP;
  
  CLOSE cur;

END*/