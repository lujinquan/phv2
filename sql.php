/**
 * 同步楼栋表[ph_ban => ph_ban_back]
 * 字段：楼栋编号、栋号、单元数量、楼层数量、楼栋地址、管段、所、产别、产权、产权来源、建成年份、栋系数、完损等级、结构类别
 * 民建面、机建面、企建面、民栋数、机栋数、企栋数、民规租、机规租、企规租、民原价、机原价、企原价、使面
 */
# 同步数据
insert into phv2.ph_ban_back 
(ban_number,ban_door,ban_units,ban_floors,ban_address,ban_inst_id,ban_inst_pid,ban_owner_id,ban_property_id,ban_property_source,ban_build_year,ban_ratio,ban_damage_id,ban_struct_id,ban_civil_area,ban_party_area,ban_career_area,ban_civil_num,ban_party_num,ban_career_num,ban_civil_rent,ban_party_rent,ban_career_rent,ban_civil_oprice,ban_party_oprice,ban_career_oprice,ban_use_area) 
select 
BanID,BanNumber,BanUnitNum,BanFloorNum,AreaFour,TubulationID,InstitutionID,OwnerType,BanPropertyID,PropertySource,BanYear,BanRatio,DamageGrade,StructureType,CivilArea,PartyArea,EnterpriseArea,CivilNum,PartyNum,EnterpriseNum,CivilRent,PartyRent,EnterpriseRent,CivilOprice,PartyOprice,EnterpriseOprice,BanUsearea
from phv1.ph_ban limit 10;
# 清空数据
# truncate phv2.ph_ban_back;



/**
 * 同步房屋表[ph_house => ph_house_back]
 * 字段：房屋编号、规租、计算租金、单元号、楼层号、门牌号、使用性质、使用面积、建面、计租面积、原价、泵费、租差、协议租金
 */
# 同步数据
insert into phv2.ph_house_back 
(house_number,house_pre_rent,house_cou_rent,house_unit_id,house_floor_id,house_door,house_use_id,house_use_area,house_area,house_lease_area,house_oprice,house_pump_rent,house_diff_rent,house_protocol_rent) 
select 
HouseID,HousePrerent,ApprovedRent,UnitID,FloorID,DoorID,UseNature,HouseUsearea,HouseArea,LeasedArea,OldOprice,PumpCost,DiffRent,ProtocolRent
from phv1.ph_house limit 10;
# 清空数据
# truncate phv2.ph_house_back;