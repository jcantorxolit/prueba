INSERT INTO system_parameters (namespace, `group`, item, value, code) VALUES
  ('wgroup', 'project_performance_level', 'Muy Alto', '100', null),
  ('wgroup', 'project_performance_level', 'Alto', '85', 100),
  ('wgroup', 'project_performance_level', 'Medio', '60', 85),
  ('wgroup', 'project_performance_level', 'Bajo', '0', 60);


delete
from system_parameters
where namespace = 'wgroup' and `group` = 'project_concepts';

delete
from system_parameters
where namespace = 'wgroup' and `group` = 'project_classifications';

INSERT INTO system_parameters (namespace, `group`, item, value, code) VALUES
  ('wgroup', 'project_concepts', 'Gastos Admon', 'PCOSGA', NULL),
  ('wgroup', 'project_concepts', 'Costos 1', 'PCOS001', 'Intm'),
  ('wgroup', 'project_concepts', 'Costos 2', 'PCOS002', 'Intm'),
  ('wgroup', 'project_concepts', 'Costos 3', 'PCOS003', 'Intm'),
  ('wgroup', 'project_concepts', 'Costos 4', 'PCOS004', 'Con'),
  ('wgroup', 'project_concepts', 'Costos 5', 'PCOS005', 'Con'),
  ('wgroup', 'project_concepts', 'Costos 6', 'PCOS006', 'OdeS'),
  ('wgroup', 'project_concepts', 'Costos 7', 'PCOS007', 'OdeS'),

  ('wgroup', 'project_classifications', 'Clasificacion Admon', 'PCLADM', 'PCOSGA'),
  ('wgroup', 'project_classifications', 'Clasificacion 1', 'PCL001', 'PCOS001'),
  ('wgroup', 'project_classifications', 'Clasificación 2', 'PCl002', 'PCOS001'),
  ('wgroup', 'project_classifications', 'Clasificación 3', 'PCl003', 'PCOS001'),
  ('wgroup', 'project_classifications', 'Clasificación 4', 'PCl004', 'PCOS002'),
  ('wgroup', 'project_classifications', 'Clasificación 5', 'PCl005', 'PCOS002'),
  ('wgroup', 'project_classifications', 'Clasificación 6', 'PCl006', 'PCOS003'),
  ('wgroup', 'project_classifications', 'Clasificación 7', 'PCl007', 'PCOS004');


alter table wg_customer_arl_contribution
  add percent_reinvestment_arl int default 0 comment 'Porcentaje de reinversión de la ARL' after input;

alter table wg_customer_arl_contribution
  add percent_reinvestment_wg int default 0 comment 'Porcentaje de reinversión waygroup.' after percent_reinvestment_arl;


create table wg_customer_project_costs
(
  id int auto_increment comment 'Identificador principal',
  project_id int null comment 'Id del proyecto',
  concept varchar(10) null comment 'concepto',
  classification varchar(10) null comment 'clasificación',
  amount int null comment 'Cantidad',
  unit_price decimal(19, 4) null comment 'Precio unitario',
  total_price decimal(19, 4) null comment 'Precio total',
  constraint wg_customer_project_costs_pk primary key (id)
) comment 'Costos asociados a un proyecto';



CREATE TABLE wg_customer_vr_satisfactions_answers_types
(
  id int AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador de la tabla',
  code varchar(10) COMMENT 'Código del grupo de la respuesta',
  answer varchar(200) COMMENT 'Respuesta',
  `order` tinyint COMMENT 'Orden',
  color varchar(7) COMMENT 'Color representativo para los indicadores'
) COMMENT 'Almanecar los tipos de respuestas disponibles agrupados por un código';


CREATE TABLE wg_customer_vr_satisfactions_questions
(
  id int AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador de la tabla',
  customer_id int comment 'Identificador del cliente',
  experience varchar(20) COMMENT 'experiencia',
  title varchar(250) COMMENT 'La pregunta',
  label varchar(20) COMMENT 'Abreviación de la pregunta',
  answer_type varchar(10) COMMENT 'Grupo de posibles respuestas disponibles'
) COMMENT 'Almacenar las preguntas que se podrán asociar por experiencia';


CREATE TABLE wg_customer_vr_satisfactions_responses
(
  id int AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador de la tabla',
  date_register datetime comment 'Fecha de registro',
  `group` varchar(100) comment 'Lote de respuestas en una sola ocasión (un mismo registro de Excel)',
  customer_id int comment 'Identificador del cliente',
  question_id int COMMENT 'Identificador de la pregunta',
  answer_id int COMMENT 'La respuesta asignada',
  created_at datetime comment 'Fecha que se creó',
  updated_at datetime comment 'Fecha de última actualización',
  created_by int comment 'Usuario que registro las respuestas'
) COMMENT 'Almacena las respuestas realizadas';


INSERT INTO system_parameters (namespace, `group`, item, value, code)
VALUES ('wgroup', 'project_type', 'Realidad Virtual', 'RV', NULL);


INSERT INTO wg_budget (item, description, classification)
VALUES ('Realidad Virtual', 'Realidad Virtual', 'RV');


create table project_consolidate (
  id int auto_increment primary key,
  deliveryDate datetime,
  type varchar(20),
  concept varchar(20),
  classification varchar(20),
  total decimal(19, 4)
);


drop table if exists wg_customer_vr_employee_consolidate;
create table wg_customer_vr_employee_consolidate (
  id int auto_increment primary key comment 'Identificador único',
  date datetime comment 'Fecha de registro',
  experience varchar(20) comment 'código de la experiencia',
  total int comment 'cantidad de personas'
);


DROP TABLE IF EXISTS wg_customer_project_agent_consolidate;
CREATE TABLE wg_customer_project_agent_consolidate (
    id int auto_increment primary key comment 'Identificador único',
    agent_id int comment 'Id del asesor',
    project_id int NULL comment 'Id del proyecto',
    delivery_date_project datetime,
    estimated_hours int,
    start_date_task datetime,
    end_date_task datetime,
    status varchar(10) NULL
);


INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES
  (1, 'ANS001', 'Muy malo', 1, '#cb3434'),
  (2, 'ANS001', 'Malo', 2, '#ff0000'),
  (3, 'ANS001', 'Regular', 3, '#ff7f27'),
  (4, 'ANS001', 'Bueno', 4, '#b5e61d'),
  (5, 'ANS001', 'Excelente', 5, '#22b14c'),
  (6, 'ANS002', 'Definitivamente No', 1, '#cb3434'),
  (7, 'ANS002', 'Probablemente No', 2, '#ff0000'),
  (8, 'ANS002', 'Indiferente', 3, '#ff7f27'),
  (9, 'ANS002', 'Probablemente Si', 4, '#b5e61d'),
  (10, 'ANS002', 'Definitivamente Si', 5, '#22b14c');


INSERT INTO wg_customer_vr_satisfactions_questions (id, customer_id, experience, title, answer_type, label) VALUES
  (1, 6, '1930', 'Nivel de Satisfacción', 'ANS001', 'Nivel. Satis.'),
  (2, 6, '1930', 'Disminución de la Accidentalidad', 'ANS002', 'Dis. Accid.'),
  (3, 6, '1932', 'Nivel de Satisfacción', 'ANS001', 'Nivel. Satis.'),
  (4, 6, '1932', 'Disminución de la Accidentalidad', 'ANS002', 'Dis. Accid.'),
  (5, 6, '1933', 'Nivel de Satisfacción', 'ANS001', 'Nivel. Satis.'),
  (6, 6, '1933', 'Disminución de la Accidentalidad', 'ANS002', 'Dis. Accid.'),
  (7, 6, '1933', 'Prueba Pregunta Adicional', 'ANS001', 'Pr. Adicional');


CREATE INDEX idx_wg_customer_project_agent_project_id
    ON wg_customer_project_agent (project_id, agent_id);


CREATE INDEX idx_wg_customer_project_agent_task_project_agent_id
    ON wg_customer_project_agent_task (project_agent_id);


