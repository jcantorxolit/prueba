
insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'positiva_fgn_config_sectionals', 'positiva_fgn_config_sectionals', now(), now());

-- Professional
insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'positiva_fgn_config_professional', 'positiva_fgn_config_professional', now(), now());

ALTER TABLE waygroup_soft_bolivar_upgrade.wg_positiva_fgn_sectional ADD nit varchar(100) NULL AFTER regional_id;

RENAME TABLE wg_positiva_fgn_campus_professional TO wg_positiva_fgn_professionals;
ALTER TABLE wg_positiva_fgn_professionals DROP COLUMN campus_id;

CREATE TABLE IF NOT EXISTS `wg_positiva_fgn_sectional_professionals` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Clave primaria',
  `professional_id` int(11) NOT NULL COMMENT 'Id empleado',
  `regional_id` int(11) NOT NULL COMMENT 'Id regional',
  `sectional_id` int(11) NOT NULL COMMENT 'Id seccional',
  `is_active` tinyint(4) DEFAULT 1,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wg_positiva_fgn_management_indicator_header;
CREATE TABLE IF NOT EXISTS `wg_positiva_fgn_management_indicator_header` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Clave primaria',
  `management_indicador_id` INT UNSIGNED NOT NULL COMMENT 'Id del indicador',
  `management_indicador_type` varchar(20) COMMENT 'Id del indicador',
  `advice_type` varchar(100) default null,
  `observation` text default null,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wg_positiva_fgn_management_indicator_log;
CREATE TABLE IF NOT EXISTS `wg_positiva_fgn_management_indicator_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Clave primaria',
  `management_indicador_id` INT UNSIGNED NOT NULL COMMENT 'Id del indicador',
  `management_indicador_type` varchar(20) COMMENT 'Id del indicador',
  `description` text default null,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE wg_positiva_fgn_activity_indicator
DROP COLUMN   `jan`,
DROP COLUMN  `feb` ,
DROP COLUMN  `mar` ,
DROP COLUMN  `apr` ,
DROP COLUMN  `may` ,
DROP COLUMN  `jun` ,
DROP COLUMN  `jul` ,
DROP COLUMN  `aug` ,
DROP COLUMN  `sep` ,
DROP COLUMN  `oct` ,
DROP COLUMN  `nov` ,
DROP COLUMN  `dec` ,
DROP COLUMN  `goal` ;

ALTER TABLE wg_positiva_fgn_gestpos
DROP COLUMN   `advice_type`;

INSERT INTO system_parameters (namespace, `group`, item, value, code) VALUES
    ('wgroup', 'positiva_fgn_strategy_type', 'Base', 'STT001', NULL),
    ('wgroup', 'positiva_fgn_strategy_type', 'Ocasional', 'STT002', NULL);

ALTER TABLE wg_positiva_fgn_consultant_strategy ADD `type` varchar(100) null after strategy;

INSERT INTO system_parameters (namespace, `group`, item, value, code) VALUES
    ('wgroup', 'positiva_fgn_gestpos_activity_states', 'EJECUTADA', 'AS001', NULL),
    ('wgroup', 'positiva_fgn_gestpos_activity_states', 'FALLIDA', 'AS002', NULL),
    ('wgroup', 'positiva_fgn_gestpos_activity_states', 'CANCELADA', 'AS003', NULL),
    ('wgroup', 'positiva_fgn_gestpos_activity_states', 'REPROGRAMADA', 'AS004', NULL);


ALTER TABLE wg_positiva_fgn_management_indicator_header ADD activity_state varchar(100) NULL AFTER advice_type;
ALTER TABLE wg_positiva_fgn_management_indicator_header ADD satisfaction_indicator_45 varchar(100) NULL AFTER observation;
ALTER TABLE wg_positiva_fgn_management_indicator_header ADD satisfaction_indicator_123 varchar(100) NULL AFTER satisfaction_indicator_45;

/*Modificaciones ACTIVIDADES DE FGN - Santiago Arango*/
ALTER TABLE wg_positiva_fgn_activity ADD COLUMN `group` VARCHAR(15) AFTER `type`;
ALTER TABLE wg_positiva_fgn_activity ADD COLUMN `goal_annual` VARCHAR(15) AFTER `type`;

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'positiva_fgn_activity_group', 'grupo uno', 'AG001', NULL);

/*Modulo de indicadores general*/
CREATE TABLE IF NOT EXISTS `wg_positiva_fgn_indicators_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `title` varchar(100) NOT NULL COMMENT 'Title of indicator',
  `description` varchar(100) DEFAULT NULL COMMENT 'Indicator description',
  `code` varchar(50) not null COMMENT 'Indicator code',
  `is_active` tinyint(4) DEFAULT 1 COMMENT 'Status indicator',
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into wg_positiva_fgn_indicators_config(`title`,`description`,`code`,`is_active`,`created_at`)VALUES('Indicador de cumplimiento actividades PTA','Genera el indicador de cumplimiento actividades PTA','IPFGN-001',1,now());
insert into wg_positiva_fgn_indicators_config(`title`,`description`,`code`,`is_active`,`created_at`)VALUES('Indicador de actividades fallidas','Genera el indicador de de actividades fallidas','IPFGN-002',1,now());
insert into wg_positiva_fgn_indicators_config(`title`,`description`,`code`,`is_active`,`created_at`)VALUES('Consolidado de indicadores','Genera el consolidado de indicadores','IPFGN-003',1,now());
insert into wg_positiva_fgn_indicators_config(`title`,`description`,`code`,`is_active`,`created_at`)VALUES('Consolidado de indicadores por seccional y actividad','Genera el consolidado de indicadores por seccional y actividad','IPFGN-004',1,now());
insert into wg_positiva_fgn_indicators_config(`title`,`description`,`code`,`is_active`,`created_at`)VALUES('Actividades por estrategia','Genera el indicador de actividades por estrategia','IPFGN-005',1,now());
insert into wg_positiva_fgn_indicators_config(`title`,`description`,`code`,`is_active`,`created_at`)VALUES('Actividades por asesor','Genera el indicador de actividades por asesor','IPFGN-006',1,now());
insert into wg_positiva_fgn_indicators_config(`title`,`description`,`code`,`is_active`,`created_at`)VALUES('Indicador de cumplimiento por eje','Genera el indicador de cumplimiento por eje','IPFGN-007',1,now());
insert into wg_positiva_fgn_indicators_config(`title`,`description`,`code`,`is_active`,`created_at`)VALUES('Indicador de cobertura por eje','Genera el indicador cobertura por eje','IPFGN-008',1,now());


CREATE TABLE `wg_positiva_fgn_management_indicator_compliance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `indicator_compliance_id` bigint(20) unsigned NOT NULL COMMENT 'relationship to wg_positiva_fgn_management_indicator_compliance',
  `management_type` varchar(15) NOT NULL COMMENT 'define if its programming or execution',
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  `programmed` varchar(100) DEFAULT '0',
  `executed` varchar(100) DEFAULT '0',
  `hour_programmed` varchar(100) DEFAULT '0',
  `hour_executed` varchar(100) DEFAULT '0',
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

ALTER TABLE wg_positiva_fgn_management_indicator_coverage_poblation ADD `date` DATETIME DEFAULT now() null after assistants;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage_poblation ADD identity_group varchar(100) after assistants;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage_poblation ADD activity_state varchar(100) NULL AFTER identity_group;

ALTER TABLE wg_positiva_fgn_management_indicator_compliance_logs ADD activity_state varchar(100) NULL AFTER date;


-- 30/03/2021 16:00

DROP TABLE IF EXISTS wg_positiva_fgn_activity_indicator_sectional_relation;
CREATE TABLE `wg_positiva_fgn_activity_indicator_sectional_relation` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fgn_activity_id` bigint(20) unsigned DEFAULT NULL,
  `sectional_id` bigint(20) unsigned DEFAULT NULL,
  `regional_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS wg_positiva_fgn_activity_indicator_sectional;
CREATE TABLE `wg_positiva_fgn_activity_indicator_sectional` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sectional_relation_id` bigint(20) unsigned DEFAULT NULL,
  `activity_indicator_id` bigint(20) unsigned DEFAULT NULL,
  `jan` varchar(10) DEFAULT '0',
  `feb` varchar(10) DEFAULT '0',
  `mar` varchar(10) DEFAULT '0',
  `apr` varchar(10) DEFAULT '0',
  `may` varchar(10) DEFAULT '0',
  `jun` varchar(10) DEFAULT '0',
  `jul` varchar(10) DEFAULT '0',
  `aug` varchar(10) DEFAULT '0',
  `sep` varchar(10) DEFAULT '0',
  `oct` varchar(10) DEFAULT '0',
  `nov` varchar(10) DEFAULT '0',
  `dec` varchar(10) DEFAULT '0',
  `goal` varchar(50) DEFAULT '0',
  `assignment` varchar(50) DEFAULT '0',
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE wg_positiva_fgn_activity_config CHANGE activity_id gestpos_activity_id bigint(20) unsigned NULL;

DROP TABLE IF EXISTS wg_positiva_fgn_activity_indicator_sectional_consultant_relation;
CREATE TABLE `wg_positiva_fgn_activity_indicator_sectional_consultant_relation` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sectional_relation_id` bigint(20) unsigned DEFAULT NULL,
  `activity_config_id` bigint(20) unsigned NOT NULL,
  `consultant_id` bigint(20) unsigned DEFAULT NULL,
  `is_occasional` tinyint(1) DEFAULT 0,
    `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wg_positiva_fgn_activity_indicator_sectional_consultant;
CREATE TABLE `wg_positiva_fgn_activity_indicator_sectional_consultant` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `consultant_relation_id` bigint(20) unsigned DEFAULT NULL,
  `activity_indicator_id` bigint(20) unsigned DEFAULT NULL,
  `jan` varchar(10) DEFAULT '0',
  `feb` varchar(10) DEFAULT '0',
  `mar` varchar(10) DEFAULT '0',
  `apr` varchar(10) DEFAULT '0',
  `may` varchar(10) DEFAULT '0',
  `jun` varchar(10) DEFAULT '0',
  `jul` varchar(10) DEFAULT '0',
  `aug` varchar(10) DEFAULT '0',
  `sep` varchar(10) DEFAULT '0',
  `oct` varchar(10) DEFAULT '0',
  `nov` varchar(10) DEFAULT '0',
  `dec` varchar(10) DEFAULT '0',
  `goal` varchar(50) DEFAULT '0',
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- management
RENAME TABLE wg_positiva_fgn_management_indicator_header TO wg_positiva_fgn_management_indicator_relation;

ALTER TABLE wg_positiva_fgn_management_indicator_relation DROP COLUMN management_indicador_id;
ALTER TABLE wg_positiva_fgn_management_indicator_relation DROP COLUMN management_indicador_type;
ALTER TABLE wg_positiva_fgn_management_indicator_relation ADD period INT DEFAULT NULL AFTER id;
ALTER TABLE wg_positiva_fgn_management_indicator_relation ADD sectional_consultant_relation_id BIGINT DEFAULT NULL AFTER satisfaction_indicator_123;

ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN fgn_activity_id;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN axis;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN strategy;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN modality;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN regional_id;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN sectional_id;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN consultant_id;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN activity_id;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN gestpos_task_id;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN gestpos_subtask_id;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance DROP COLUMN period;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance ADD indicator_relation_id BIGINT DEFAULT NULL AFTER hour_executed;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance ADD activity_gestpos_id BIGINT DEFAULT NULL AFTER indicator_relation_id;


ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN fgn_activity_id;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN axis;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN strategy;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN modality;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN sectional_id;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN regional_id;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN consultant_id;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN activity_id;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN gestpos_task_id;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN gestpos_subtask_id;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage DROP COLUMN period;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage ADD indicator_relation_id BIGINT DEFAULT NULL AFTER assistants;
ALTER TABLE wg_positiva_fgn_management_indicator_coverage ADD activity_gestpos_id BIGINT DEFAULT NULL AFTER indicator_relation_id;

-- 31-03-2021 14:20
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `jan`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `feb`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `mar`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `apr`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `may`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `jun`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `jul`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `aug`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `sep`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `oct`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `nov`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `dec`;
ALTER TABLE wg_positiva_fgn_activity_staging DROP COLUMN `goal`; 

ALTER TABLE wg_positiva_fgn_management_indicator_log CHANGE management_indicador_type advice_type varchar(20);
ALTER TABLE wg_positiva_fgn_management_indicator_relation DROP COLUMN activity_state;
ALTER TABLE wg_positiva_fgn_consolidated_indicators ADD `group` VARCHAR(10) DEFAULT NULL COMMENT 'Group of the fgn activity';

ALTER TABLE wg_positiva_fgn_consultant_staging CHANGE adminisionDate admission_date date NULL;
ALTER TABLE wg_positiva_fgn_consultant_staging CHANGE mainContact main_contact varchar(200) DEFAULT '0' NULL;
ALTER TABLE wg_positiva_fgn_consultant_staging CHANGE workingDay working_day varchar(200) DEFAULT '0' NULL;
ALTER TABLE wg_positiva_fgn_consultant_staging MODIFY COLUMN eps varchar(200) DEFAULT '0' NULL;
ALTER TABLE wg_positiva_fgn_consultant_staging MODIFY COLUMN ccf varchar(200) DEFAULT '0' NULL;
ALTER TABLE wg_positiva_fgn_consultant_staging MODIFY COLUMN afp varchar(200) DEFAULT '0' NULL;



DROP PROCEDURE IF EXISTS TL_PF_FGN_Consultant;

DELIMITER $$
$$
CREATE PROCEDURE TL_PF_FGN_Consultant(IN `sessionId` varchar(255))
BEGIN


  insert into wg_positiva_fgn_consultant (`type`, document_type, document_number, full_name, gender, birth_date, job, grade, accounting_account,
                                          admission_date, withdrawal_date, profession, working_day, main_contact, telephone,
                                          eps, afp, ccf, account_type, bank, account_number, staging_id, created_by, created_at)

  select consultant_type.value as `type`, employee_document_type.value as document_type, o.identification as document_number, o.full_name,
    gender.value as gender, o.birth_date, o.job, grade.value as grade, o.accounting_account, o.admission_date, o.withdrawal_date, o.profession,
    workday.value as working_day, o.main_contact, o.phoneMainContact as telephone, eps.value as eps, afp.value as afp, ccf.value as ccf,
    account_type.value as account_type, o.bank, o.accountNumber as account_number, o.id as staging_id, o.created_by, o.created_at
  from wg_positiva_fgn_consultant_staging o
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_type'
       ) consultant_type ON o.`type` = consultant_type.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'employee_document_type'
       ) employee_document_type ON o.identificationType = employee_document_type.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'gender'
       ) gender ON o.gender = gender.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_grade'
       ) grade ON o.grade = grade.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_workday'
       ) workday ON o.working_day = workday.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'eps'
       ) eps ON o.eps = eps.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'afp'
       ) afp ON o.afp = afp.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'ccf'
       ) ccf ON o.ccf = ccf.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'account_type'
       ) account_type ON o.accountType = account_type.item
  LEFT JOIN wg_positiva_fgn_consultant d ON d.document_type = employee_document_type.value AND d.document_number = o.identification
  WHERE session_id = sessionId AND d.id is null;



  insert into wg_positiva_fgn_consultant_contact_information (consultant_id, `type`, value, created_by, created_at)

  select t.id as consultant_id, t.`type`, t.value, t.created_by, t.created_at
  from (
    select ce.id, info.value as `type`, se.email as value, se.created_by, se.created_at
    from wg_positiva_fgn_consultant_staging se
    join wg_positiva_fgn_consultant ce on ce.staging_id = se.id
    LEFT JOIN (
           SELECT item COLLATE utf8_general_ci AS  item,
             `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'extrainfo'
         ) info ON info.item = 'email'
    where se.session_id = sessionId
    union
    select ca.id, info.value as `type`,  sa.address as value , sa.created_by, sa.created_at
    from wg_positiva_fgn_consultant_staging sa
    join wg_positiva_fgn_consultant ca on ca.staging_id = sa.id
    LEFT JOIN (
           SELECT item COLLATE utf8_general_ci AS  item,
             `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'extrainfo'
         ) info ON info.item = 'Dirección'
    where sa.session_id = sessionId
    union
    select cp.id, info.value as `type`, sp.telephone as value, sp.created_by, sp.created_at
    from wg_positiva_fgn_consultant_staging sp
    join wg_positiva_fgn_consultant cp on cp.staging_id = sp.id
    LEFT JOIN (
           SELECT item COLLATE utf8_general_ci AS  item,
             `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'extrainfo'
         ) info ON info.item = 'Teléfono'
    where sp.session_id = sessionId
    UNION
    select cc.id, info.value as `type`, sc.cellphone as value, sc.created_by, sc.created_at
    from wg_positiva_fgn_consultant_staging sc
    join wg_positiva_fgn_consultant cc on cc.staging_id = sc.id
    LEFT JOIN (
           SELECT item COLLATE utf8_general_ci AS  item,
             `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'extrainfo'
         ) info ON info.item = 'Celular'
    where sc.session_id = sessionId
  ) t
  LEFT JOIN wg_positiva_fgn_consultant_contact_information d ON d.consultant_id = t.id
  WHERE d.id is null;




  insert into wg_positiva_fgn_consultant_license (consultant_id, license, expedition_date, issuing_entity, created_by, created_at)

  select c.id, sstLicense as license, st.expedition_date, st.issuingEntity as issuing_entity,
    st.created_by, st.created_at
  from wg_positiva_fgn_consultant_staging st
  join wg_positiva_fgn_consultant c on c.staging_id = st.id
  left join wg_positiva_fgn_consultant_license d on d.consultant_id = c.id
  WHERE session_id = sessionId and d.id is null;



  INSERT INTO wg_positiva_fgn_consultant_strategy (consultant_id, strategy, created_by, created_at)
  SELECT
    wg_positiva_fgn_consultant.id as consultant_id,
    O.value,
    wg_positiva_fgn_consultant.created_by,
    wg_positiva_fgn_consultant.created_at
  FROM (
    SELECT O.id, positiva_fgn_consultant_strategy.value, 'STT001' as `type`
    FROM wg_positiva_fgn_consultant_staging O
    JOIN (
           SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy1 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT O.id, positiva_fgn_consultant_strategy.value, 'STT002' as `type`
    FROM wg_positiva_fgn_consultant_staging O
    JOIN (
           SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy2 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT O.id, positiva_fgn_consultant_strategy.value, 'STT002' as `type`
    FROM wg_positiva_fgn_consultant_staging O
    JOIN (
           SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy3 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT O.id, positiva_fgn_consultant_strategy.value, 'STT002' as `type`
    FROM wg_positiva_fgn_consultant_staging O
    JOIN (
           SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy4 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
  ) O
  join wg_positiva_fgn_consultant on wg_positiva_fgn_consultant.staging_id = O.id
  left join wg_positiva_fgn_consultant_strategy d on d.consultant_id = wg_positiva_fgn_consultant.id
  WHERE d.id IS null;

END$$
DELIMITER ;



DROP PROCEDURE IF EXISTS TL_PF_Gestpos_Activity;

CREATE PROCEDURE TL_PF_Gestpos_Activity(IN `sessionId` varchar(255))
BEGIN

  -- activity
  insert into wg_positiva_fgn_gestpos (`type`, name, sector, program, plan, action_line, consecutive, activity_type,
                                       created_by, created_at, staging_id, is_active, is_automatic)

  select O.`type`, O.name, sector.value as sector, program.value as program, plan.value as plan, action_line.value as action_line,
    O.consecutive, activity_type.value as activity_type, O.created_by, O.created_at,
    O.id as staging_id, O.is_active, O.is_automatic
  from wg_positiva_gestpos_task_staging O
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_sector'
       ) sector ON O.sector = sector.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_program'
       ) program ON O.program = program.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_plan'
       ) plan ON O.plan = plan.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_action_line'
       ) action_line ON O.action_line = action_line.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_activity_type'
       ) activity_type ON O.activity_type = activity_type.item
  LEFT JOIN wg_positiva_fgn_gestpos D on D.name = O.name and
    D.sector = sector.value and
    D.program = program.value and
    D.plan = plan.value and
    D.action_line = action_line.value and
    D.consecutive = O.consecutive and
    D.activity_type = activity_type.value
  WHERE O.session_id = sessionId and D.id is null;


  -- TASK AUTOMATIC
  insert into wg_positiva_fgn_gestpos (`type`, name, `number`, created_by, created_at, staging_id)

  select 'main' as `type`, O.name, (select max(`number`) + 1 from wg_positiva_fgn_gestpos) as `number`, O.created_by, O.created_at, O.id as staging_id
  from wg_positiva_gestpos_task_staging O
  LEFT JOIN wg_positiva_fgn_gestpos D ON D.name = O.name and D.type = 'main'
  WHERE O.is_automatic = 1 and O.session_id = sessionId;


  --  asociated automatic task
  INSERT INTO wg_positiva_fgn_gestpos_associated_task (gestpos_id, task_id, created_by, created_at)

  SELECT A.id as gestpos_id, T.id as task_id,
    wg_positiva_gestpos_task_staging.created_by, wg_positiva_gestpos_task_staging.created_at
  from wg_positiva_gestpos_task_staging
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_sector'
       ) sector ON wg_positiva_gestpos_task_staging.sector = sector.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_program'
       ) program ON wg_positiva_gestpos_task_staging.program = program.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_plan'
       ) plan ON wg_positiva_gestpos_task_staging.plan = plan.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_action_line'
       ) action_line ON wg_positiva_gestpos_task_staging.action_line = action_line.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_activity_type'
       ) activity_type ON wg_positiva_gestpos_task_staging.activity_type = activity_type.item
  JOIN wg_positiva_fgn_gestpos A on A.name = wg_positiva_gestpos_task_staging.name and
    A.sector = sector.value and
    A.program = program.value and
    A.plan = plan.value and
    A.action_line = action_line.value and
    A.consecutive = wg_positiva_gestpos_task_staging.consecutive and
    A.activity_type = activity_type.value
  JOIN wg_positiva_fgn_gestpos T ON wg_positiva_gestpos_task_staging.id = T.staging_id and T.`type` = 'main' and wg_positiva_gestpos_task_staging.name = T.name
  LEFT JOIN wg_positiva_fgn_gestpos_associated_task D on D.gestpos_id = A.id and D.task_id = T.id
  WHERE wg_positiva_gestpos_task_staging.session_id = sessionId and D.id is null AND wg_positiva_gestpos_task_staging.is_automatic = 1
  GROUP BY A.name, A.sector, A.program, A.plan, A.plan, A.action_line, A.consecutive, A.activity_type, T.id;



  -- associated main task
  INSERT INTO wg_positiva_fgn_gestpos_associated_task (gestpos_id, task_id, created_by, created_at)

  SELECT A.id as gestpos_id, T.id as task_id,
    wg_positiva_gestpos_task_staging.created_by, wg_positiva_gestpos_task_staging.created_at
  from wg_positiva_gestpos_task_staging
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_sector'
       ) sector ON wg_positiva_gestpos_task_staging.sector = sector.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_program'
       ) program ON wg_positiva_gestpos_task_staging.program = program.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_plan'
       ) plan ON wg_positiva_gestpos_task_staging.plan = plan.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_action_line'
       ) action_line ON wg_positiva_gestpos_task_staging.action_line = action_line.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_activity_type'
       ) activity_type ON wg_positiva_gestpos_task_staging.activity_type = activity_type.item
  JOIN wg_positiva_fgn_gestpos A on A.name = wg_positiva_gestpos_task_staging.name and
    A.sector = sector.value and
    A.program = program.value and
    A.plan = plan.value and
    A.action_line = action_line.value and
    A.consecutive = wg_positiva_gestpos_task_staging.consecutive and
    A.activity_type = activity_type.value
  JOIN wg_positiva_fgn_gestpos T ON wg_positiva_gestpos_task_staging.main_task = T.`number` and T.`type` = 'main'
  LEFT JOIN wg_positiva_fgn_gestpos_associated_task D on D.gestpos_id = A.id and D.task_id = T.id
  WHERE wg_positiva_gestpos_task_staging.session_id = sessionId and D.id is null
  GROUP BY A.name, A.sector, A.program, A.plan, A.plan, A.action_line, A.consecutive, A.activity_type, T.id;




  -- STRATEGYS
  INSERT INTO wg_positiva_fgn_gestpos_strategy (gestpos_id, strategy, is_active, created_by, created_at)
  SELECT
    A.id as gestpos_id,
    O.value,
    1,
    O.created_by,
    O.created_at
  FROM (
    SELECT O.id, positiva_fgn_consultant_strategy.value, O.name, O.sector, O.program, O.plan, O.action_line, O.consecutive, O.activity_type, O.created_at, O.created_by
    FROM wg_positiva_gestpos_task_staging O
    JOIN (
           SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy1 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT O.id, positiva_fgn_consultant_strategy.value, O.name, O.sector, O.program, O.plan, O.action_line, O.consecutive, O.activity_type, O.created_at, O.created_by
    FROM wg_positiva_gestpos_task_staging O
    JOIN (
           SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy2 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT O.id, positiva_fgn_consultant_strategy.value, O.name, O.sector, O.program, O.plan, O.action_line, O.consecutive, O.activity_type, O.created_at, O.created_by
    FROM wg_positiva_gestpos_task_staging O
    JOIN (
           SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy3 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT O.id, positiva_fgn_consultant_strategy.value, O.name, O.sector, O.program, O.plan, O.action_line, O.consecutive, O.activity_type, O.created_at, O.created_by
    FROM wg_positiva_gestpos_task_staging O
    JOIN (
           SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy4 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
  ) O
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_sector'
       ) sector ON O.sector = sector.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_program'
       ) program ON O.program = program.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_plan'
       ) plan ON O.plan = plan.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_action_line'
       ) action_line ON O.action_line = action_line.item
  JOIN (
         SELECT item COLLATE utf8_general_ci AS  item,
           `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_activity_type'
       ) activity_type ON O.activity_type = activity_type.item
  JOIN wg_positiva_fgn_gestpos A on A.name = O.name and
    A.sector = sector.value and
    A.program = program.value and
    A.plan = plan.value and
    A.action_line = action_line.value and
    A.consecutive = O.consecutive and
    A.activity_type = activity_type.value
  LEFT JOIN wg_positiva_fgn_gestpos_strategy D on A.id = D.gestpos_id AND D.strategy = O.value
  WHERE D.id is null
  GROUP BY A.name, A.sector, A.program, A.plan, A.plan, A.action_line, A.consecutive, A.activity_type, O.value;

END;


alter table wg_positiva_gestpos_task_staging drop column advice_type;
alter table wg_positiva_fgn_activity_staging modify axis varchar(200) null;


DROP PROCEDURE IF EXISTS TL_PF_FGN_Activity;

CREATE PROCEDURE TL_PF_FGN_Activity(IN sessionId varchar(255))
BEGIN


  INSERT INTO wg_positiva_fgn_activity (config_id, name, code, axis, action, type, created_by, created_at)
  SELECT
    wg_positiva_fgn_config.id,
    O.activity,
    O.code,
    positiva_fgn_activity_axis.value,
    positiva_fgn_activity_action.value,
    positiva_fgn_gestpos_activity_type.value,
    O.created_by,
    O.created_at
  FROM wg_positiva_fgn_activity_staging O
  JOIN wg_positiva_fgn_config ON O.period = wg_positiva_fgn_config.period
  JOIN (
         SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_activity_axis'
       ) positiva_fgn_activity_axis ON O.axis = positiva_fgn_activity_axis.item
  JOIN (
         SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_activity_action'
       ) positiva_fgn_activity_action ON O.action = positiva_fgn_activity_action.item
  LEFT JOIN (
         SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_gestpos_activity_type'
       ) positiva_fgn_gestpos_activity_type ON O.type = positiva_fgn_gestpos_activity_type.item
  LEFT JOIN wg_positiva_fgn_activity D on O.activity = D.name AND O.code = D.code
  WHERE O.session_id = sessionId AND D.id IS NULL
  GROUP BY O.code, O.activity;


  INSERT INTO wg_positiva_fgn_activity_strategy (fgn_activity_id, strategy, created_by, created_at)
  SELECT
    A.id,
    O.value,
    O.created_by,
    O.created_at
  FROM (
    SELECT positiva_fgn_consultant_strategy.value, O.code, O.activity, O.created_by, O.created_at, O.session_id
    FROM wg_positiva_fgn_activity_staging O
    JOIN (
           SELECT
             item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy_1 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT positiva_fgn_consultant_strategy.value, O.code, O.activity, O.created_by, O.created_at, O.session_id
    FROM wg_positiva_fgn_activity_staging O
    JOIN (
           SELECT
             item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy_2 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT positiva_fgn_consultant_strategy.value, O.code, O.activity, O.created_by, O.created_at, O.session_id
    FROM wg_positiva_fgn_activity_staging O
    JOIN (
           SELECT
             item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy_3 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
    UNION ALL
    SELECT positiva_fgn_consultant_strategy.value, O.code, O.activity, O.created_by, O.created_at, O.session_id
    FROM wg_positiva_fgn_activity_staging O
    JOIN (
           SELECT
             item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
           FROM system_parameters
           WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_consultant_strategy'
         ) positiva_fgn_consultant_strategy ON O.strategy_4 = positiva_fgn_consultant_strategy.item
    WHERE session_id = sessionId
  ) O
  JOIN wg_positiva_fgn_activity A on O.activity = A.name AND O.code = A.code
  LEFT JOIN wg_positiva_fgn_activity_strategy D on A.id = D.fgn_activity_id AND D.strategy = O.value

  WHERE D.id IS NULL
  GROUP BY O.code, O.activity, O.value;


  INSERT INTO wg_positiva_fgn_activity_indicator (fgn_activity_id, type, periodicity, formulation,
                                                  created_by, created_at)
  SELECT
    A.id,
    positiva_fgn_activity_type.value,
    positiva_fgn_activity_periodicity.value,
    O.formulation,
    O.created_by,
    O.created_at
  FROM wg_positiva_fgn_activity_staging O
  JOIN (
         SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_activity_type'
       ) positiva_fgn_activity_type ON O.indicator_type = positiva_fgn_activity_type.item
  JOIN (
         SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE namespace = 'wgroup' AND `group` = 'positiva_fgn_activity_periodicity'
       ) positiva_fgn_activity_periodicity ON O.periodicity = positiva_fgn_activity_periodicity.item
  JOIN wg_positiva_fgn_activity A on O.activity = A.name AND O.code = A.code
  LEFT JOIN wg_positiva_fgn_activity_indicator D on A.id = D.fgn_activity_id AND positiva_fgn_activity_type.value = D.type

  WHERE session_id = sessionId AND D.id IS NULL
  GROUP BY O.code, O.activity, O.indicator_type;

END;

-- 06/04/2021 16:00

ALTER TABLE wg_positiva_fgn_management_indicator_coverage_poblation
CHANGE assistants `type` VARCHAR(20);

ALTER TABLE wg_positiva_fgn_management_indicator_coverage_poblation
CHANGE `call` value VARCHAR(20);

ALTER TABLE wg_positiva_fgn_management_indicator_coverage_poblation 
MODIFY COLUMN `date` DATE DEFAULT NULL;

ALTER TABLE wg_positiva_fgn_management_indicator_coverage_poblation 
DROP COLUMN identity_group;


alter table wg_positiva_fgn_consolidated_indicators modify meta_compliance varchar(100) null comment 'meta de cumplimiento configurada por seccionales';
alter table wg_positiva_fgn_consolidated_indicators modify meta_coverage varchar(100) null comment 'meta de cobertura configurada por seccional';

alter table wg_positiva_fgn_consolidated_indicators
  add meta_compliance_consultant varchar(100) null comment 'meta de cumplimiento configurada por seccional y asesor';

alter table wg_positiva_fgn_consolidated_indicators
  add meta_coverage_consultant varchar(100) null comment 'meta de cobertura configurada por seccional y asesor';



ALTER TABLE wg_positiva_fgn_management_indicator_compliance_logs ADD observation text NULL AFTER hour_executed;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance_logs ADD satisfaction_indicator_123 varchar(100) NULL AFTER observation;
ALTER TABLE wg_positiva_fgn_management_indicator_compliance_logs ADD satisfaction_indicator_45 varchar(100) NULL AFTER satisfaction_indicator_123;


-- create indexes
CREATE INDEX wg_positiva_fgn_management_indicator_relation_sectional_idx ON wg_positiva_fgn_management_indicator_relation (sectional_consultant_relation_id);
CREATE INDEX wg_positiva_fgn_management_indicator_relation_period_idx ON wg_positiva_fgn_management_indicator_relation (period);
CREATE INDEX wg_positiva_fgn_management_indicator_coverage_idx USING BTREE ON wg_positiva_fgn_management_indicator_coverage (indicator_relation_id);
CREATE INDEX wg_positiva_fgn_management_indicator_compliance_idx USING BTREE ON wg_positiva_fgn_management_indicator_compliance (indicator_relation_id);
CREATE INDEX wg_positiva_fgn_management_indicator_compliance_agest_idx ON wg_positiva_fgn_management_indicator_compliance (activity_gestpos_id);

CREATE INDEX wg_fgn_activity_indicator_sectional_consultant_relation_idx USING BTREE ON wg_positiva_fgn_activity_indicator_sectional_consultant_relation (sectional_relation_id);
CREATE INDEX wg_fgn_activity_indicator_sectional_consultant_relation_cg_idx ON wg_positiva_fgn_activity_indicator_sectional_consultant_relation (activity_config_id);

CREATE INDEX wg_positiva_fgn_activity_config_activity_idx USING BTREE ON wg_positiva_fgn_activity_config (fgn_activity_id);

CREATE INDEX wg_positiva_fgn_activity_config_subtask_idx USING BTREE ON wg_positiva_fgn_activity_config_subtask (activity_config_id);
CREATE INDEX wg_positiva_fgn_activity_config_subtask_gespost_idx ON wg_positiva_fgn_activity_config_subtask (gestpos_subtask_id);

CREATE INDEX fgn_activity_indicator_sectional_consultant_cr_idx USING BTREE ON wg_positiva_fgn_activity_indicator_sectional_consultant (consultant_relation_id);
CREATE INDEX fgn_activity_indicator_sectional_consultant_ai_idx USING BTREE ON wg_positiva_fgn_activity_indicator_sectional_consultant (activity_indicator_id);
CREATE INDEX wg_positiva_fgn_activity_indicator_afgn_idx USING BTREE ON wg_positiva_fgn_activity_indicator (fgn_activity_id);
CREATE INDEX wg_positiva_fgn_activity_indicator_type_idx ON wg_positiva_fgn_activity_indicator (type);






