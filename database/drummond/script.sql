
INSERT INTO `jbonnydev_userpermissions_permissions` (`name`, `code`, `created_at`, `updated_at`) VALUES
('app_acts_behaviors', 'app_acts_behaviors', now(), now());

DROP TABLE IF EXISTS wg_customer_employee_reportscyc_protocols_answers;
CREATE TABLE wg_customer_employee_reportscyc_protocols_answers (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`answer` varchar(50) DEFAULT NULL,
`observation` varchar(300) DEFAULT NULL,
`question_id` bigint(20) unsigned,
`user_id` bigint(20) unsigned,
`created_by` varchar(100) DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_by` varchar(100) DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_reportscyc_answer_types_options', 'Cumple', 'CUMPLE', 'AT001');


INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_reportscyc_answer_types_options', 'No Cumple', 'NOCUMPLE', 'AT001');


INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_reportscyc_answer_types_options', 'N/A', 'NA', 'AT001');


ALTER TABLE wg_customer_employee_reportscyc_protocols_answers
ADD status VARCHAR(50) DEFAULT "OPEN" AFTER user_id,
ADD observation_manage VARCHAR(500) AFTER status,
ADD date_manage datetime AFTER observation_manage;


UPDATE system_parameters SET value='27' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='RESPONSABLE SST';
UPDATE system_parameters SET value='29' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='SEGURIDAD INDUSTRIAL';
UPDATE system_parameters SET value='31' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='SALUD';
UPDATE system_parameters SET value='30' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='GRUPO APOYO SST';
UPDATE system_parameters SET value='24' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='CONDICIONES INSEGURAS';
UPDATE system_parameters SET value='22' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='CONTRATISTA';
UPDATE system_parameters SET value='39' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='PLAN DE FORMACIÓN PROFE';


ALTER TABLE wg_customer_employee_reportscyc_protocols_answers
ADD imageUrl text DEFAULT NULL AFTER date_manage;


drop table if exists wg_customer_employee_location;
create table wg_customer_employee_location (
    id int unsigned not null auto_increment,
    code varchar(20),
    name varchar(200),
    customer_id bigint unsigned,
 	primary key(id)
);

drop table if exists wg_customer_employee_department;
create table wg_customer_employee_department (
 	id int unsigned not null auto_increment,
 	code varchar(20),
 	name varchar(200),
 	customer_id bigint unsigned,
 	location_id int unsigned,
 	primary key(id)
);

drop table if exists wg_customer_employee_area;
create table wg_customer_employee_area (
 	id int unsigned not null auto_increment,
 	code varchar(20),
 	name varchar(200),
 	customer_id bigint unsigned,
 	department_id int unsigned,
 	primary key(id)
);

drop table if exists wg_customer_employee_turn;
create table wg_customer_employee_turn (
 	id int unsigned not null auto_increment,
 	code varchar(20),
 	name varchar(200),
 	customer_id bigint unsigned,
 	area_id int unsigned,
 	primary key(id)
);

ALTER TABLE wg_customer_employee ADD location_id INT UNSIGNED NULL AFTER primary_cellphone;
ALTER TABLE wg_customer_employee ADD department_id INT UNSIGNED NULL AFTER location_id;
ALTER TABLE wg_customer_employee ADD area_id INT UNSIGNED NULL AFTER department_id;
ALTER TABLE wg_customer_employee ADD turn_id int NULL AFTER area_id;



drop table if exists wg_customer_employee_reportscyc_protocols;
create table wg_customer_employee_reportscyc_protocols (
 	id int unsigned not null auto_increment,
 	name varchar(200),
 	is_active tinyint,
 	danger_type_id int,
 	customer_id bigint unsigned,
 	created_by varchar(100) NULL,
 	created_at DATETIME NULL,
 	updated_by varchar(100) NULL,
 	updated_at DATETIME NULL,
 	primary key(id)
 );

drop table if exists wg_customer_employee_reportscyc_protocols_questions;
create table wg_customer_employee_reportscyc_protocols_questions (
 	id int unsigned not null auto_increment,
 	description varchar(200),
 	answer_type varchar(50),
 	is_active tinyint,
 	protocols_id bigint unsigned,
 	primary key(id)
);

INSERT INTO system_parameters (namespace,`group`,item,value) VALUES
	('wgroup','wg_customer_reportscyc_answer_types', 'Si, No, N/A', 'AT001'),
	('wgroup','wg_customer_reportscyc_answer_types', 'Cumple, No Cumple, N/A', 'AT002');


INSERT INTO system_parameters (namespace,`group`,item,value) VALUES
    ('wgroup','wg_customer_reportscyc_responsable_types', 'Locación', 'RT001'),
    ('wgroup','wg_customer_reportscyc_responsable_types', 'Área', 'RT002');


drop table if exists wg_customer_employee_reportscyc_responsables;
create table wg_customer_employee_reportscyc_responsables (
 	id int unsigned not null auto_increment,
 	employee_id bigint unsigned,
 	responsable_type varchar(50),
 	location_id INT UNSIGNED,
 	department_id INT UNSIGNED,
 	area_id INT UNSIGNED,
    customer_id bigint unsigned,
 	created_by varchar(100) NULL,
 	created_at DATETIME NULL,
 	updated_by varchar(100) NULL,
 	updated_at DATETIME NULL,
 	primary key(id)
);

drop table if exists wg_customer_employee_reportscyc_responsable_protocols;
create table wg_customer_employee_reportscyc_responsable_protocols (
 	id int unsigned not null auto_increment,
 	responsable_id int unsigned,
 	protocol_id bigint unsigned,
 	primary key(id)
);

drop table if exists wg_customer_employee_reportscyc_employee_app;
create table wg_customer_employee_reportscyc_employee_app (
 	id int unsigned not null auto_increment,
 	employee_id bigint unsigned,
    customer_id bigint unsigned,
    is_active tinyint,
 	created_by varchar(100) NULL,
 	created_at DATETIME NULL,
 	updated_by varchar(100) NULL,
 	updated_at DATETIME NULL,
 	primary key(id)
);

INSERT INTO wg_customer_parameter (customer_id,namespace,`group`,item,value)
VALUES (34416,'wgroup','domainDefaultEmployeesApp','DD001','drummong.com.co');

INSERT INTO wg_customer_parameter (customer_id,namespace,`group`,item,value)
VALUES (34416,'wgroup','employeesOrganizationalStructure','OS001','1');


CREATE TABLE `wg_customer_employee_organizational_structure_staging` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `identificationType` varchar(15) DEFAULT NULL,
  `identification` varchar(16) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `area` varchar(200) DEFAULT NULL,
  `turn` varchar(200) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `session_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);



CREATE TABLE `wg_customer_employee_reportscyc_responsable_staging` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `responsableType` varchar(20) DEFAULT NULL,
  `identificationType` varchar(15) DEFAULT NULL,
  `identification` varchar(16) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `profile` varchar(200) DEFAULT NULL,
  `role` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `area` varchar(200) DEFAULT NULL,
  `protocol` varchar(200) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `session_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);


DELIMITER $$
CREATE PROCEDURE TL_CUSTOMER_EMPLOYEE_OS (IN `sessionId` varchar(255))
BEGIN
update wg_customer_employee as des
inner join (
    select e.id, o.customer_id, loc.id as location_id, dep.id as department_id, ar.id as area_id, tu.id as turn_id
    from wg_customer_employee_organizational_structure_staging o
    join wg_employee e on e.documentType = o.identificationType and e.documentNumber = o.identification
    join wg_customer_employee ce on ce.employee_id = e.id and ce.customer_id = o.customer_id
    join wg_customer_employee_location loc on loc.name = o.location and loc.customer_id = ce.customer_id
    join wg_customer_employee_department dep on dep.name = o.department and dep.customer_id = ce.customer_id
    join wg_customer_employee_area ar on ar.name = o.area and ar.customer_id = ce.customer_id
    join wg_customer_employee_turn tu on tu.name = o.turn and tu.customer_id = ce.customer_id
    join wg_customer_organizational_structure os on os.customer_id = ce.customer_id
        and os.location_id = loc.id
        and os.department_id = dep.id
        and os.area_id = ar.id
        and os.turn_id = tu.id
    where o.session_id = sessionId
) as src on des.employee_id = src.id and des.customer_id = src.customer_id
set des.location_id = src.location_id,
    des.department_id = src.department_id,
    des.area_id = src.area_id,
    des.turn_id = src.turn_id;

END $$

alter table wg_customer_employee_reportscyc_responsables add column session_id varchar(100) after area_id;
alter table wg_customer_employee_reportscyc_responsable_staging add column `password` varchar(100) after protocol;

DROP PROCEDURE IF EXISTS TL_CUSTOMER_REPORTSCYC_RESPONSABLE;

DELIMITER $$
CREATE PROCEDURE TL_CUSTOMER_REPORTSCYC_RESPONSABLE (IN `sessionId` varchar(255))
BEGIN

    -- save responsable
    insert into wg_customer_employee_reportscyc_responsables (employee_id, responsable_type, location_id,
        department_id, area_id, customer_id, staging_id)

    select
        e.id as employee_id,
        prt.value responsable_type,
        loc.id location_id,
        dep.id department_id,
        ar.id area_id,
        o.customer_id,
        o.id as staging_id
    from
        wg_customer_employee_reportscyc_responsable_staging o
            join wg_customer_employee ce on ce.customer_id = o.customer_id
            join wg_employee e on
                    e.id = ce.employee_id
                and e.documentType = o.identificationType
                and e.documentNumber = o.identification
            join system_parameters prt on
                    prt.`group` = 'wg_customer_reportscyc_responsable_types'
                and prt.item COLLATE utf8_general_ci = o.responsableType
            join wg_customer_employee_location loc on
                    loc.name = o.location
                and loc.customer_id = o.customer_id
            left join wg_customer_employee_department dep on
                    dep.name = o.department
                and dep.customer_id = o.customer_id
                and prt.value = 'RT002'
            left join wg_customer_employee_area ar on
                    ar.name = o.area
                and ar.customer_id = o.customer_id
                and prt.value = 'RT002'
            join wg_customer_organizational_structure os on (
                (prt.value = 'RT001' and os.location_id = loc.id) or
                (prt.value = 'RT002' and os.location_id = loc.id and os.department_id = dep.id and os.area_id = ar.id)
            )
            left join wg_customer_employee_reportscyc_responsables des on des.customer_id = o.customer_id
                and (e.id = des.employee_id
                    or (
                        (prt.value = 'RT001' and des.responsable_type = 'RT001' and loc.id = des.location_id)
                    )
                )
    where
            o.`session_id` = sessionId and des.id is null and length(o.email) > 1
      and IF(prt.value = 'RT001', 1, IF(dep.id is null, 0, 1) ) = 1
      and IF(prt.value = 'RT001', 1, IF(ar.id  is null, 0, 1) ) = 1
    group by
        e.id, o.location;


    -- save email
    insert wg_employee_info_detail (entityName, entityId, `type`, value)

    select 'Wgroup\\\Employee\\\Employee' as entityName, e.id as entityId, 'email' as `type`, o.email
    from wg_customer_employee_reportscyc_responsable_staging o
             join wg_customer_employee_reportscyc_responsables r on r.staging_id = o.id
             join wg_employee e on e.id = r.employee_id
             left join wg_employee_info_detail i on i.entityId = e.id
        and i.entityName = 'Wgroup\\\Employee\\\Employee' and i.`type` = 'email'
    where o.`session_id` = sessionId and i.id is null and length(o.email) > 1
    group by e.id;


    -- update primary email
    update wg_customer_employee des
        join (
            select i.id, i.entityId
            from wg_customer_employee_reportscyc_responsable_staging o
                     join wg_customer_employee_reportscyc_responsables r on r.staging_id = o.id
                     join wg_employee e on e.id = r.employee_id
                     join wg_employee_info_detail i on i.entityId = e.id
                and i.entityName = 'Wgroup\\\Employee\\\Employee' and i.`type` = 'email' and o.email = i.value
            where o.`session_id` = sessionId
        ) src on src.entityId = des.employee_id
    SET des.primary_email = src.id;


    -- save protocols
    insert into wg_customer_employee_reportscyc_responsable_protocols (responsable_id, protocol_id)

    select r.id responsable_id, pr.id protocol_id
    from wg_customer_employee_reportscyc_responsable_staging o
             join wg_customer_employee_reportscyc_responsables r on r.staging_id = o.id
             join system_parameters prt on
                prt.`group` = 'wg_customer_reportscyc_responsable_types'
            and prt.item COLLATE utf8_general_ci = o.responsableType
            and prt.value = 'RT002'
             join wg_customer_employee_reportscyc_protocols pr on pr.name = o.protocol
             left join wg_customer_employee_reportscyc_responsable_protocols rp on rp.responsable_id = r.id and rp.protocol_id = pr.id
    where o.`session_id` = sessionId and rp.id is null and length(o.email) > 1;


    -- save users
    insert into users(name, surname, email, username,  company, wg_type, iu_comment, `password`, is_activated)

    select e.firstName as name, e.lastName as surname, i.value as email, i.value as username,
           o.customer_id as company, pp.value as wg_type, e.documentNumber as iu_comment, o.`password` as `password`, 1 as is_activated
    from wg_customer_employee_reportscyc_responsable_staging o
     join wg_customer_employee_reportscyc_responsables r on r.staging_id = o.id
     join wg_employee e on e.id = r.employee_id
     join system_parameters pp on pp.`group` = 'wg_customer_user_profile' and pp.item COLLATE utf8_general_ci = o.profile
     join wg_employee_info_detail i on i.entityId = e.id
        and i.entityName = 'Wgroup\\\Employee\\\Employee' and i.`type` = 'email' and LENGTH(i.value) > 1
             left join users des on des.email = i.value
             left join wg_employee_info_detail i2 on i2.entityId <> e.id
        and i2.entityName = 'Wgroup\\\Employee\\\Employee' and i2.`type` = 'email' and i2.value = des.email
    where o.`session_id` = sessionId and des.id is null and i2.id is null
    GROUP by e.id;


    -- save customer user
    insert into wg_customer_user (firstName, lastName, documentType, documentNumber, gender, `type`, email,
                                  isActive, profile, `role`, user_id, module)

    select e.firstName, e.lastName, e.documentType, e.documentNumber, e.gender, '01' as `type`,
           u.email as email, 1 as isActive, pp.value profile, pr.value `role`, u.id as user_id, 'RE' as module
    from
        wg_customer_employee_reportscyc_responsable_staging o
            join wg_customer_employee_reportscyc_responsables r on r.staging_id = o.id
            join wg_employee e on e.id = r.employee_id
            join system_parameters pp on pp.`group` = 'wg_customer_user_profile' and pp.item COLLATE utf8_general_ci = o.profile
            join system_parameters pr on pr.`group` = 'customer_user_role' and pr.item COLLATE utf8_general_ci = o.`role`
            join wg_employee_info_detail i on i.entityId = e.id
            and i.entityName = 'Wgroup\\\Employee\\\Employee' and i.`type` = 'email' and LENGTH(i.value) > 1
            join users u on u.email = i.value
            left join wg_customer_user des on u.id = des.user_id
    where
            o.`session_id` = sessionId and des.id is null
    group by
        e.id;


    -- update info customer user
    update wg_customer_user as des
        inner join (
            select
                cu.id as customer_user_id,
                o.customer_id,
                e.firstName, e.lastName,
                e.documentType, e.documentNumber,
                e.gender, '01' as `type`,
                pp.value profile,
                pr.value `role`,
                u.id as user_id
            from
                wg_customer_employee_reportscyc_responsable_staging o
                    join wg_customer_employee_reportscyc_responsables r on r.staging_id = o.id
                    join wg_employee e on e.id = r.employee_id
                    join system_parameters pp on pp.`group` = 'wg_customer_user_profile' and pp.item COLLATE utf8_general_ci = o.profile
                    join system_parameters pr on pr.`group` = 'customer_user_role' and pr.item COLLATE utf8_general_ci = o.`role`
                    join wg_employee_info_detail i on i.entityId = e.id
                    and i.entityName = 'Wgroup\\\Employee\\\Employee' and i.`type` = 'email' and LENGTH(i.value) > 1
                    join users u on u.email = i.value
                    join wg_customer_user cu on u.id = cu.user_id
            where
                    o.`session_id` = sessionId
            group by
                e.id
        ) src on src.customer_user_id = des.id
    set
        des.customer_id = src.customer_id,
        des.firstName = src.firstName,
        des.lastName = src.lastName,
        des.documentType = src.documentType,
        des.documentNumber = src.documentNumber,
        des.gender = src.gender, des.`type` = src.`type`, des.isActive = 1,
        des.profile = src.profile, des.`role` = src.`role`;



    -- remove old roles
    delete from users_groups
    where user_id in (
        select distinct u.id as user_id
        from wg_customer_employee_reportscyc_responsable_staging o
                 join wg_customer_employee_reportscyc_responsables r on r.staging_id = o.id
                 join wg_employee e on e.id = r.employee_id
                 join wg_employee_info_detail i on i.entityId = e.id
            and i.entityName = 'Wgroup\\\Employee\\\Employee' and i.`type` = 'email'
                 join users u on u.email = i.value
        where o.session_id = sessionId
    );

    -- assign new role
    insert into users_groups (user_id, user_group_id)

    select u.id as user_id, pr.value as user_group_id
    from wg_customer_employee_reportscyc_responsable_staging o
             join wg_customer_employee_reportscyc_responsables r on r.staging_id = o.id
             join wg_employee e on e.id = r.employee_id
             join wg_employee_info_detail i on i.entityId = e.id
        and i.entityName = 'Wgroup\\\Employee\\\Employee' and i.`type` = 'email'
             join users u on u.email = i.value
             join wg_customer_user cu on cu.user_id = u.id
             join system_parameters pr on
                pr.`group` = 'customer_user_role'
            and pr.value COLLATE utf8_general_ci = cu.`role`
    where o.session_id = sessionId;


END $$





CREATE TABLE `wg_customer_employee_reportscyc_employee_app_staging` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(20) DEFAULT NULL,
  `identificationType` varchar(15) DEFAULT NULL,
  `identification` varchar(16) DEFAULT NULL,
  `profile` varchar(200) DEFAULT NULL,
  `role` varchar(200) DEFAULT NULL,
  `state` varchar(10),
  `password` varchar(100),
  `session_id` varchar(100) NOT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);



DELIMITER $$
CREATE PROCEDURE TL_CUSTOMER_REPORTSCYC_EMPLOYEEAPP (IN `sessionId` varchar(255))
BEGIN
	-- Save employee app
	INSERT INTO wg_customer_employee_reportscyc_employee_app (employee_id, customer_id, is_active, created_by, created_at)

	select e.id as employee_id, o.customer_id, if(o.state = 'Activo', 1, 0) as is_active, o.created_by, o.created_at
	from wg_customer_employee_reportscyc_employee_app_staging o
	join wg_employee e on e.documentType = o.identificationType and e.documentNumber = o.identification
	join wg_customer_employee ce on ce.employee_id = e.id and o.customer_id = ce.customer_id
	left join wg_customer_employee_reportscyc_employee_app des on des.employee_id = e.id and des.customer_id = o.customer_id
	where o.session_id = sessionId and des.id is null
	group by e.id;


	-- save user
	insert into users (name, surname, email, username, company, wg_type, iu_about, iu_comment, `password`, is_activated)

	select
		e.firstName as name,
		e.lastName as surname,
		CONCAT(
			e.documentType, e.documentNumber, '@',
			COALESCE(SUBSTRING_INDEX(REPLACE(SUBSTRING_INDEX(webSite, '//',-1), 'www.', ''), '/', 1), cpd.value)
		) email,
		CONCAT(
			e.documentType, e.documentNumber, '@',
			COALESCE(SUBSTRING_INDEX(REPLACE(SUBSTRING_INDEX(webSite, '//',-1), 'www.', ''), '/', 1), cpd.value)
		) username,
		o.customer_id as company,
		pp.value as wg_type,
		e.documentNumber as iu_about,
		e.documentNumber as iu_comment,
		o.`password` as `password`,
		if(o.state = 'Activo', 1, 0) as is_activated
	from
		wg_customer_employee_reportscyc_employee_app_staging o
	join wg_employee e on
		e.documentType = o.identificationType
		and e.documentNumber = o.identification
	join wg_customer_employee ce on
		ce.employee_id = e.id
		and o.customer_id = ce.customer_id
	join wg_customers c on c.id = ce.customer_id
	join wg_customer_parameter cpd on
		cpd.customer_id = c.id
		and cpd.`group` = 'domainDefaultEmployeesApp'
		and cpd.item COLLATE utf8_general_ci = 'DD001'
	join system_parameters pp on
		pp.`group` = 'wg_customer_user_profile'
		and pp.item COLLATE utf8_general_ci = IF(LENGTH(o.profile) > 1, o.profile, 'Cliente Asesor')
	left join users des on
		cast(des.company as char) = cast(o.customer_id as char)
		and cast(des.iu_about as char) = o.identification
	where
		o.session_id = sessionId and des.id is null
	group by e.id;


	-- save customer user
	insert into wg_customer_user (customer_id, firstName, lastName, documentType, documentNumber, gender, `type`, email,
	        isActive, profile, `role`, user_id, isUserApp, module)

	select
	    o.customer_id,
	    e.firstName,
	    e.lastName,
	    e.documentType,
	    e.documentNumber,
	    e.gender,
	    '01' as `type`,
	    u.email,
	    if(o.state = 'Activo', 1, 0) as isActive,
	    pp.value profile,
	    pr.value `role`,
	    u.id as user_id,
	    1 as isUserApp,
	    'EA' as module
	from
		wg_customer_employee_reportscyc_employee_app_staging o
	join wg_employee e on
		e.documentType = o.identificationType
		and e.documentNumber = o.identification
	join wg_customer_employee ce on
		ce.employee_id = e.id
		and o.customer_id = ce.customer_id
	join wg_customers c on c.id = ce.customer_id
	join wg_customer_parameter cpd on
		cpd.customer_id = c.id
		and cpd.`group` = 'domainDefaultEmployeesApp'
		and cpd.item COLLATE utf8_general_ci = 'DD001'
	join system_parameters pp on
	    pp.`group` = 'wg_customer_user_profile'
	    and pp.item COLLATE utf8_general_ci = IF(LENGTH(o.profile) > 1, o.profile, 'Cliente Asesor')
	join system_parameters pr on
	    pr.`group` = 'customer_user_role'
	    and pr.item COLLATE utf8_general_ci = IF(LENGTH(o.`role`) > 1, o.`role`, 'CONDICIONES INSEGURAS')
	join users u on
		cast(u.company as char) = cast(o.customer_id as char)
	    and cast(u.iu_about as char) = o.identification
	left join wg_customer_user cu on
	    cu.customer_id = o.customer_id
	    and cu.user_id = u.id
	where o.session_id = sessionId and cu.id is null
	group by e.id;



    -- update state in Employees App
    update wg_customer_employee_reportscyc_employee_app as des
    inner join (
        select e.id as employee_id, if(o.state = 'Activo', 1, 0) as is_active
        from wg_customer_employee_reportscyc_employee_app_staging o
        join wg_employee e on e.documentType = o.identificationType and e.documentNumber = o.identification
        join wg_customer_employee ce on ce.employee_id = e.id and o.customer_id = ce.customer_id
        where o.session_id = sessionId
        group by e.id
    ) as src on src.employee_id = des.employee_id
    set des.is_active = src.is_active;

	-- update state customer user
	update wg_customer_user as des
	inner join (
		select cu.id as customerUserId, if(o.state = 'Activo', 1, 0) as is_active, e.documentType, e.documentNumber
	    from wg_customer_employee_reportscyc_employee_app_staging o
	    join wg_employee e on e.documentType = o.identificationType and e.documentNumber = o.identification
	    join wg_customer_employee ce on ce.employee_id = e.id and o.customer_id = ce.customer_id
	    join wg_customers c on c.id = ce.customer_id
	    join wg_customer_parameter cpd on
			cpd.customer_id = c.id
			and cpd.`group` = 'domainDefaultEmployeesApp'
			and cpd.item COLLATE utf8_general_ci = 'DD001'
	    join users u on
		    cast(u.company as char) = cast(o.customer_id as char)
            and cast(u.iu_about as char) = o.identification
		join wg_customer_user cu on
		    cu.customer_id = o.customer_id
		    and cu.user_id = u.id
	    where o.session_id = sessionId
	    group by e.id
	) as src on src.customerUserId = des.id
	set des.isActive = src.is_active,
		des.documentType = src.documentType,
		des.documentNumber = src.documentNumber;


	-- update state in users
	update users as d
	join (
		select u.id as user_id, if(o.state = 'Activo', 1, 0) as is_activated
		from wg_customer_employee_reportscyc_employee_app_staging o
		join wg_employee e on e.documentType = o.identificationType and e.documentNumber = o.identification
		join wg_customer_employee ce on ce.employee_id = e.id and o.customer_id = ce.customer_id
		join wg_customers c on c.id = ce.customer_id
		join wg_customer_parameter cpd on
			cpd.customer_id = c.id
			and cpd.`group` = 'domainDefaultEmployeesApp'
		join users u on
		    cast(u.company as char) = cast(o.customer_id as char)
            and cast(u.iu_about as char) = o.identification
		join wg_customer_user cu on
		    cu.customer_id = o.customer_id
		    and cu.user_id = u.id
		where o.session_id = sessionId
		group by e.id
	) as src on src.user_id = d.id
	set
		d.is_activated = src.is_activated;



	-- assign role
	insert into users_groups (user_id, user_group_id)
	select u.id as user_id, pr.value as user_group_id
	from wg_customer_employee_reportscyc_employee_app_staging o
	join wg_employee e on e.documentType = o.identificationType and e.documentNumber = o.identification
	join wg_customer_employee ce on ce.employee_id = e.id and o.customer_id = ce.customer_id
	join wg_customers c on c.id = ce.customer_id
    join wg_customer_parameter cpd on
		cpd.customer_id = c.id
		and cpd.`group` = 'domainDefaultEmployeesApp'
		and cpd.item COLLATE utf8_general_ci = 'DD001'
	join users u on
	    cast(u.company as char) = cast(o.customer_id as char)
	    and cast(u.iu_about as char) = o.identification
	join wg_customer_user cu on
		    cu.customer_id = o.customer_id
		    and cu.user_id = u.id
	join system_parameters pr on
		pr.`group` = 'customer_user_role'
	    and pr.value COLLATE utf8_general_ci = cu.`role`
	left join users_groups d on d.user_id = u.id and d.user_group_id = pr.value
	where o.session_id = sessionId and d.user_id is null
	group by u.id, pr.value;


	update users_groups as d
	join (
		select u.id as user_id, cu.`role` as old_role, pr.value as new_role
		from wg_customer_employee_reportscyc_employee_app_staging o
		join wg_employee e on e.documentType = o.identificationType and e.documentNumber = o.identification
		join wg_customer_employee ce on ce.employee_id = e.id and o.customer_id = ce.customer_id
		join wg_customers c on c.id = ce.customer_id
	    join wg_customer_parameter cpd on
			cpd.customer_id = c.id
			and cpd.`group` = 'domainDefaultEmployeesApp'
			and cpd.item COLLATE utf8_general_ci = 'DD001'
	    join users u on
		    cast(u.company as char) = cast(o.customer_id as char)
		    and cast(u.iu_about as char) = o.identification
		join wg_customer_user cu on
		    cu.customer_id = o.customer_id
		    and cu.user_id = u.id
		join system_parameters pr on
			pr.`group` = 'customer_user_role'
		    and pr.item COLLATE utf8_general_ci = o.`role`
		where o.session_id = sessionId
		group by u.id, pr.value
	) src on src.user_id = d.user_id and src.old_role = d.user_group_id
	set d.user_group_id = src.new_role;

END $$


insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'customer_reports_cyc', 'customer_reports_cyc', now(), now());

insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'customer_reports_cyc_config', 'customer_reports_cyc_config', now(), now());

insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'customer_reports_cyc_reports', 'customer_reports_cyc_reports', now(), now());


CREATE TABLE `wg_customer_organizational_structure_staging` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `area` varchar(200) DEFAULT NULL,
  `turn` varchar(200) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `session_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);


DELIMITER $$
CREATE PROCEDURE TL_CUSTOMER_ORGANIZATIONAL_STRUCTURE (IN `sessionId` varchar(255))
BEGIN

	-- save locations
	INSERT INTO wg_customer_employee_location (name, customer_id)

	select o.location as name, c.id as customer_id
	from wg_customer_organizational_structure_staging o
	join wg_customers c on c.id = o.customer_id
	left join wg_customer_employee_location d on d.name = o.location and d.customer_id = o.customer_id
	where o.session_id = sessionId AND d.id IS NULL
        AND LENGTH(o.location) > 0
        AND LENGTH(o.department) > 0
        AND LENGTH(o.area) > 0
        AND LENGTH(o.turn) > 0
	group by o.location;

	-- save departments
	INSERT INTO wg_customer_employee_department (name, customer_id)

    select o.department as name, c.id as customer_id
    from wg_customer_organizational_structure_staging o
    join wg_customers c on c.id = o.customer_id
    left join wg_customer_employee_department d on d.name = o.department and c.id = d.customer_id
    where o.session_id = sessionId AND d.id is null
      AND LENGTH(o.location) > 0
      AND LENGTH(o.department) > 0
      AND LENGTH(o.area) > 0
      AND LENGTH(o.turn) > 0
    group by o.department;

	-- save areas
	INSERT INTO wg_customer_employee_area (name, customer_id)

	select o.area as name, c.id as customer_id
	from wg_customer_organizational_structure_staging o
	join wg_customers c on c.id = o.customer_id
	left join wg_customer_employee_area d on d.name = o.area and c.id = d.customer_id
	where o.session_id = sessionId AND d.id is null
        AND LENGTH(o.location) > 0
        AND LENGTH(o.department) > 0
        AND LENGTH(o.area) > 0
        AND LENGTH(o.turn) > 0
	group by o.area;

	-- save turns
	INSERT INTO wg_customer_employee_turn (name, customer_id)

	select o.turn as name, c.id as customer_id
	from wg_customer_organizational_structure_staging o
	join wg_customers c on c.id = o.customer_id
	left join wg_customer_employee_turn d on d.name = o.turn and c.id = d.customer_id
	where o.session_id = sessionId AND d.id is null
        AND LENGTH(o.location) > 0
        AND LENGTH(o.department) > 0
        AND LENGTH(o.area) > 0
        AND LENGTH(o.turn) > 0
	group by o.turn;

	-- save structure
    insert into wg_customer_organizational_structure (customer_id, location_id, department_id, area_id, turn_id, session_id)

    select
        c.id as customer_id,
        l.id as location_id,
        dep.id as department_id,
        a.id as area_id,
        t.id as turn_id,
        o.id as session_id
    from
        wg_customer_organizational_structure_staging o
    join wg_customers c on
        c.id = o.customer_id
    join wg_customer_employee_location l on
        l.name = o.location
        and l.customer_id = o.customer_id
    join wg_customer_employee_department dep on
        dep.name = o.department
        and dep.customer_id = o.customer_id
    join wg_customer_employee_area a on
        a.name = o.area
        and a.customer_id = o.customer_id
    join wg_customer_employee_turn t on
        t.name = o.turn
        and t.customer_id = o.customer_id
    left join wg_customer_organizational_structure d on
        d.customer_id = c.id
        and d.location_id = l.id
        and d.department_id = dep.id
        and d.area_id = a.id
        and d.turn_id = t.id
	where o.session_id = sessionId;

END $$


insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'cliente_organizational_structure', 'cliente_organizational_structure', now(), now());

insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'cliente_organizational_structure_employee_import', 'cliente_organizational_structure_employee_import', now(), now());


CREATE TABLE wg_customer_organizational_structure (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `location_id` int unsigned DEFAULT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `area_id` int unsigned DEFAULT NULL,
  `turn_id` int unsigned DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `session_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);

ALTER TABLE wg_customer_employee_department DROP COLUMN location_id;
ALTER TABLE wg_customer_employee_area DROP COLUMN department_id;
ALTER TABLE wg_customer_employee_turn DROP COLUMN area_id;


ALTER TABLE wg_customer_employee_reportscyc_protocols_questions MODIFY COLUMN description varchar(300) DEFAULT NULL;

INSERT INTO wg_customer_employee_location (code,name,customer_id) VALUES
('Pribbenow','Pribbenow',17),
('El Descanso','El Descanso',17),
('Transporte','Transporte',17),
('Valledupar','Valledupar',17),
('Bogotá','Bogotá',17),
('Cartagena','Cartagena',17);


alter table wg_customer_user add column module varchar(200) default null after user_id;



INSERT INTO `jbonnydev_userpermissions_permissions` (`name`, `code`, `created_at`, `updated_at`) VALUES
('app_acts_behaviors', 'app_acts_behaviors', now(), now());

DROP TABLE IF EXISTS wg_customer_employee_reportscyc_protocols_answers;
CREATE TABLE wg_customer_employee_reportscyc_protocols_answers (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`answer` varchar(50) DEFAULT NULL,
`observation` varchar(300) DEFAULT NULL,
`question_id` bigint(20) unsigned,
`user_id` bigint(20) unsigned,
`created_by` varchar(100) DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_by` varchar(100) DEFAULT NULL,
`updated_at` datetime DEFAULT NULL,
PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_reportscyc_answer_types_options', 'Si', 'SI', 'AT001');


INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_reportscyc_answer_types_options', 'No', 'NO', 'AT001');


INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_reportscyc_answer_types_options', 'Cumple', 'CUMPLE', 'AT002');


INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_reportscyc_answer_types_options', 'No Cumple', 'NOCUMPLE', 'AT002');


INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_reportscyc_answer_types_options', 'N/A', 'NA', 'AT000');


ALTER TABLE wg_customer_employee_reportscyc_protocols_answers
ADD status VARCHAR(50) DEFAULT "OPEN" AFTER user_id,
ADD observation_manage VARCHAR(500) AFTER status,
ADD date_manage datetime AFTER observation_manage;

ALTER TABLE wg_customer_employee_reportscyc_protocols_answers
ADD responsable_id bigint(20) unsigned AFTER user_id,
ADD customer_id bigint(20) unsigned AFTER responsable_id;

ALTER TABLE wg_customer_employee_reportscyc_protocols_answers
ADD turn_id bigint(20) unsigned AFTER customer_id,
ADD code_complete varchar(50) AFTER turn_id;


UPDATE system_parameters SET value='27' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='RESPONSABLE SST';
UPDATE system_parameters SET value='29' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='SEGURIDAD INDUSTRIAL';
UPDATE system_parameters SET value='31' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='SALUD';
UPDATE system_parameters SET value='30' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='GRUPO APOYO SST';
UPDATE system_parameters SET value='24' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='CONDICIONES INSEGURAS';
UPDATE system_parameters SET value='22' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='CONTRATISTA';
UPDATE system_parameters SET value='39' WHERE namespace='wgroup' AND `group`='customer_user_role' AND item='PLAN DE FORMACIÓN PROFE';


ALTER TABLE wg_customer_employee_reportscyc_protocols_answers
ADD image_url text DEFAULT NULL AFTER date_manage;


ALTER TABLE wg_customer_employee_reportscyc_protocols_questions
ADD `created_by` varchar(100) DEFAULT NULL AFTER protocols_id,
ADD  `created_at` datetime DEFAULT NULL AFTER created_by,
ADD  `updated_by` varchar(100) DEFAULT NULL AFTER created_at,
ADD  `updated_at` datetime DEFAULT NULL AFTER updated_by;


INSERT INTO system_mail_templates ( code, subject, description, content_html, content_text, layout_id, is_custom, created_at, updated_at) VALUES( 'rainlab.user::mail.notification_protocol_acts_behaviors', 'Guardián - Condiciones y Comportamientos {{ origin }}', 'Notificaciones para los responsables de las condiciones y comportamientos', '
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tbody>
        <tr>
            <td bgcolor="#ffffff" align="center" style="padding: 15px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;"
                    class="responsive-table">
                    <tbody>
                        <tr>
                            <td>
                                <!-- COPY -->
                                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        <tr>
                                            <td align="center"
                                                style="font-size: 32px; font-family: Helvetica, Arial, sans-serif; color: #333333; padding-top: 30px;"
                                                class="padding-copy">
                                                Tiene una notificación !
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left"
                                                style="padding: 20px 0 0 0; font-size: 16px; line-height: 25px; font-family: Helvetica, Arial, sans-serif; color: #666666;"
                                                class="padding-copy">
Se ha realizado un reporte de Protocolo en Condiciones y Comportamientos.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#ffffff" align="center" style="padding: 15px;" class="padding">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px;"
                    class="responsive-table">
                    <tbody>
                        <tr>
                            <td style="padding: 10px 0 0 0; border-top: 1px dashed #aaaaaa;">
                                <!-- TWO COLUMNS -->
                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td valign="top" class="mobile-wrapper">
                                                <!-- LEFT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="left">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="left"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                Protocolo
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <!-- RIGHT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="right">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="right"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                {{ protocol }}
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0 0 0; border-top: 1px dashed #aaaaaa;">
                                <!-- TWO COLUMNS -->
                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td valign="top" class="mobile-wrapper">
                                                <!-- LEFT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="left">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="left"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                Fecha
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <!-- RIGHT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="right">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="right"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                {{ date }}
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <!-- TWO COLUMNS -->
                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td valign="top" style="padding: 0;" class="mobile-wrapper">
                                                <!-- LEFT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="left">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="left"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                Área
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <!-- RIGHT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="right">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="right"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                {{ area }}
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <!-- TWO COLUMNS -->
                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td valign="top" style="padding: 0;" class="mobile-wrapper">
                                                <!-- LEFT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="left">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="left"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                Tipo de Peligro
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <!-- RIGHT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="right">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="right"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                {{ dangerType }}
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td valign="top" style="padding: 0;" class="mobile-wrapper">
                                                <!-- LEFT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="left">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="left"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                Reportado por:</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <!-- RIGHT COLUMN -->
                                                <table cellpadding="0" cellspacing="0" border="0" width="47%"
                                                    style="width: 47%;" align="right">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0;">
                                                                <table cellpadding="0" cellspacing="0" border="0"
                                                                    width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="right"
                                                                                style="font-family: Arial, sans-serif; color: #333333; font-size: 16px;">
                                                                                {{ reportBy }}
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td id="footer"
                style="background-color: transparent;padding: 20px;font-size: 10px;color: #666;line-height: 100%;font-family: Georgia;text-align: center;">
                <img src="https://guardiandelaproductividad.com/themes/wgroup/assets/images/logo.png" width="400"
                    height="389" data-verified="redactor">
            </td>
        </tr>
    </tbody>
</table>
<p><br></p>
', '', 1, 1, now(), now());



ALTER TABLE wg_customer_employee_reportscyc_protocols_answers
ADD responsable_employee_id bigint(20) unsigned AFTER date_manage;
