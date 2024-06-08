ALTER TABLE wg_customer_project_agent_task
  ADD duration int COMMENT 'Duración de la tarea en horas' AFTER endDateTime;

update wg_customer_project_agent_task
set duration = TIMESTAMPDIFF(HOUR, startDateTime, endDateTime);

INSERT INTO system_parameters (namespace, `group`, item, value, code) VALUES
  ('wgroup', 'projects_time_to_allow_create_task', '5', 'T001', null),
  ('wgroup', 'customers_projects_sales_status', 'PROGRAMADA', 'SS001', null),
  ('wgroup', 'customers_projects_sales_status', 'EJECUTADA',  'SS002', null);


INSERT INTO `jbonnydev_userpermissions_permissions` (`name`, `code`, `created_at`, `updated_at`) VALUES
  ('projects_show_comments_card', 'projects_show_comments_card', now(), now()),
  ('projects_show_attachments_card', 'projects_show_attachments_card', now(), now()),
  ('projects_attachments', 'projects_attachments', now(), now());


CREATE TABLE wg_customer_project_document (
  id bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id bigint NOT NULL,
  type varchar(10) NOT NULL,
  origin varchar(20) NOT NULL,
  classification varchar(10) NULL,
  description text NULL,
  status varchar(10) NOT NULL,
  version int NOT NULL,
  label varchar(20) NULL,
  created_at datetime NULL COMMENT 'Fecha Creado',
  created_by int NULL COMMENT 'Creado Por',
  updated_at datetime NULL COMMENT 'Fecha Actualización',
  updated_by int NULL COMMENT 'Actualizado Por'
);

CREATE INDEX idx_customer_project_document_project_id ON wg_customer_project_document (project_id);



CREATE TABLE wg_customer_project_comments (
  id int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_project_id int(11) UNSIGNED NOT NULL,
  comment text NULL,
  type varchar(4) NULL,
  created_at datetime NULL COMMENT 'Fecha Creado',
  created_by int NULL COMMENT 'Creado Por',
  updated_at datetime NULL COMMENT 'Fecha Actualización',
  updated_by int NULL COMMENT 'Actualizado Por'
);

CREATE INDEX idx_wg_customer_project_comments_created_by ON wg_customer_project_comments (customer_project_id);


ALTER TABLE wg_customer_project_costs ADD status varchar(10) NULL COMMENT 'Estado de la venta';
ALTER TABLE wg_customer_project_costs ADD created_at datetime NULL;
ALTER TABLE wg_customer_project_costs ADD created_by int NULL;
ALTER TABLE wg_customer_project_costs ADD updated_at datetime NULL;
ALTER TABLE wg_customer_project_costs ADD updated_by int NULL;



-- segunda milestone

INSERT INTO system_parameters (namespace, `group`, item, value, code)
VALUES ('wgroup', 'project_type', 'SYLOGI', 'SYL', NULL),
  ('wgroup', 'project_concepts', 'INTERMEDIACIÓN', 'PCOS014', 'SYL'),
  ('wgroup', 'project_classifications', 'INTERMEDIACIÓN', 'PCl035', 'PCOS014'),

  ('wgroup', 'project_concepts', 'INTERMEDIACIÓN', 'C03', 'RV'),
  ('wgroup', 'project_classifications', 'INTERMEDIACIÓN', 'PCl036', 'C03');


INSERT INTO wg_budget (item, description, classification)
VALUES ('SYLOGI', 'SYLOGI', 'SYL');


CREATE INDEX idx_customer_project_customer_id ON wg_customer_project (customer_id);
CREATE INDEX idx_customer_project_delivery_date ON wg_customer_project (deliveryDate);
CREATE INDEX idx_customer_project_type ON wg_customer_project (type);

CREATE INDEX idx_customer_project_costs_concept ON wg_customer_project_costs (concept);
CREATE INDEX idx_customer_project_costs_customer_id ON wg_customer_project_costs (project_id);
CREATE INDEX idx_customer_project_costs_status ON wg_customer_project_costs (status);

CREATE INDEX idx_customer_arl_contributions_customer_id ON wg_customer_arl_contribution (customer_id, id);
CREATE INDEX idx_customer_arl_contributions_month ON wg_customer_arl_contribution (month);
CREATE INDEX idx_customer_arl_contributions_year ON wg_customer_arl_contribution (year);


ALTER TABLE project_consolidate ADD customer_id int NULL AFTER classification;
ALTER TABLE project_consolidate ADD administrator int NULL AFTER customer_id;
ALTER TABLE project_consolidate ADD total_executed decimal(19, 4) NULL;
ALTER TABLE project_consolidate ADD total_programmed decimal(19, 4) NULL;


CREATE INDEX idx_project_consolidate_administrator ON project_consolidate (administrator);
CREATE INDEX idx_project_consolidate_customer_id ON project_consolidate (customer_id);
CREATE INDEX idx_project_consolidate_delivary_date ON project_consolidate (deliveryDate);
CREATE INDEX idx_project_consolidate_type ON project_consolidate (type);


-- tercer milestone
INSERT INTO system_parameters (namespace, `group`, item, value, code) VALUES
  ('wgroup', 'project_type_group', 'OdeS', 'SST', NULL),
  ('wgroup', 'project_type_group', 'Con', 'SST', NULL),
  ('wgroup', 'project_type_group', 'Intm', 'SST', NULL),
  ('wgroup', 'project_type_group', 'OdeSNC', 'SST', NULL);
