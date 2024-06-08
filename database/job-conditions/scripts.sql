-- Permisos
INSERT INTO `jbonnydev_userpermissions_permissions` (`name`, `code`, `created_at`, `updated_at`) VALUES
('open_job_conditions', 'open_job_conditions', now(), now());

INSERT INTO `jbonnydev_userpermissions_permissions` (`name`, `code`, `created_at`, `updated_at`) VALUES
('job_conditions_register', 'job_conditions_register', now(), now());

INSERT INTO `jbonnydev_userpermissions_permissions` (`name`, `code`, `created_at`, `updated_at`) VALUES
('job_conditions_indicators', 'job_conditions_indicators', now(), now());

INSERT INTO `jbonnydev_userpermissions_permissions` (`name`, `code`, `created_at`, `updated_at`) VALUES
('view_job_conditions', 'view_job_conditions', now(), now());

INSERT INTO `jbonnydev_userpermissions_permissions` (`name`, `code`, `created_at`, `updated_at`) VALUES
('edit_conditions_register', 'edit_conditions_register', now(), now());

/*System Parameters*/
/*
NAME_PARAMETER: wg_customer_job_conditions_work_model
DESCRIPTION: Indica el modelo de trabajo de las condiciones laborales
--
NAME_PARAMETER: wg_customer_job_conditions_location
DESCRIPTION: Indica las ubicaciones de las condiciones de trabajo
--
NAME_PARAMETER: wg_customer_job_conditions_location
DESCRIPTION: Indica las ubicaciones de las condiciones de trabajo
--
*/
INSERT INTO system_parameters (namespace, `group`, item, value, code) VALUES
  ('wgroup', 'wg_customer_job_conditions_work_model', 'Presencial', 'JWM001', 'JWM001'),
  ('wgroup', 'wg_customer_job_conditions_work_model', 'Teletrabajo', 'JWM002', 'JWM002'),
  ('wgroup', 'wg_customer_job_conditions_location', 'Oficina Principal', 'JCL001', 'JCL001'),
  ('wgroup', 'wg_customer_job_conditions_location', 'Casa', 'JCL002', 'JCL002'),
  ('wgroup', 'wg_customer_job_conditions_location', 'Centro Comercial', 'JCL003', 'JCL003'),
  ('wgroup', 'wg_customer_job_conditions_answer_types', 'CUMPLE', 'JCA001', 'JCA001'),
  ('wgroup', 'wg_customer_job_conditions_answer_types', 'NO CUMPLE', 'JCA002', 'JCA002'),
  ('wgroup', 'wg_customer_job_conditions_answer_types', 'NO APLICA', 'JCA003', 'JCA003');

-- Consultas
DROP TABLE IF EXISTS wg_customer_job_condition;
CREATE TABLE wg_customer_job_condition (
`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Campo identificador de la tabla',
`customer_employee_id` bigint(20) unsigned COMMENT 'Campo identificador del empleado',
`immediate_boss_id` bigint(20) unsigned COMMENT 'Campo identificador del jefe inmediato',
`state` varchar(20) COMMENT 'Campo que indica el estado de la condición de trabajo',
`customer_id` bigint unsigned COMMENT 'Campo identificador del cliente' null,
`staging_id` nvarchar(200) null COMMENT 'Campo identificador de la tabla de staging',
`created_by` varchar(100) DEFAULT null COMMENT 'Campo fecha creación del registro o insert de la información',
`created_at` datetime DEFAULT null COMMENT 'Campo identificador del usuario que registra o inserta la información',
`updated_by` varchar(100) DEFAULT null COMMENT 'Campo fecha actualización de la información',
`updated_at` datetime DEFAULT null COMMENT 'Campo identificador del usuario que actualiza la información',
PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacena la información de las condiciones puestos de trabajo';

DROP TABLE IF EXISTS wg_customer_job_condition_self_evaluation;
CREATE TABLE wg_customer_job_condition_self_evaluation (
`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Campo identificador de la tabla',
`job_condition_id` bigint(20) unsigned COMMENT 'Campo identificador de la tabla condiciones puestos de trabajo',
`registration_date` date COMMENT 'Campo fecha registra la autoevaluación',
`work_model` varchar(100) COMMENT 'Campo identificador del modelo de trabajo',
`location` varchar(100) COMMENT 'Campo identificador de la ubicación',
`workplace_id` bigint(20) unsigned COMMENT 'Campo identificador del puesto de trabajo',
`risk` int NULL COMMENT 'Porcentaje de riesgo actual',
`risk_initial` int NULL COMMENT 'Porcentaje de riesgo inicial',
`occupationId` int null COMMENT 'Ocupación del empleado asignada en la evaluación',
`state` tinyint COMMENT 'Indica el estado del proceso',
`created_by` varchar(100) DEFAULT null COMMENT 'Campo fecha creación del registro o insert de la información',
`created_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que registra o inserta la información',
`updated_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha actualización de la información',
`updated_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que actualiza la información',
PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacena la información de las auto evaluaciones enlazadas a una condición de puesto de trabajo';

DROP TABLE IF EXISTS wg_customer_job_condition_self_evaluation_answers;
CREATE TABLE wg_customer_job_condition_self_evaluation_answers (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Campo identificador de la tabla',
`self_evaluation_id` bigint(20) unsigned COMMENT 'Campo identificador de la tabla creación autoevaluación',
`question_id` bigint(20) unsigned COMMENT 'Campo identificador de la tabla donde se almacenan las preguntas de las autoevaluaciones',
`answer` varchar(100) COMMENT 'Campo respuesta a la pregunta de la autoevaluación',
`initial` tinyint null COMMENT 'Campo que indica si la respuesta es inicial',
`created_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha creación del registro o insert de la información',
`created_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que registra o inserta la información',
`updated_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha actualización de la información',
`updated_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que actualiza la información',
PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacenan las respuestas de las autoevaluaciones';


-- MAESTROS

DROP TABLE IF EXISTS wg_customer_job_condition_workplace;
CREATE TABLE wg_customer_job_condition_workplace (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Campo identificador de la tabla',
`name` varchar(200) COMMENT 'Campo nombre del puesto de trabajo',
`created_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha creación del registro o insert de la información',
`created_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que registra o inserta la información',
`updated_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha actualización de la información',
`updated_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que actualiza la información',
PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacenan los lugares de trabajo';

DROP TABLE IF EXISTS wg_customer_job_condition_classification;
CREATE TABLE wg_customer_job_condition_classification (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Campo identificador de la tabla',
`name` varchar(200) COMMENT 'Campo nombre de la clasificación de la condición de validación del puesto de trabajo',
`parent_id` bigint(20) unsigned COMMENT 'Campo identificador de una condición segmentada de otra',
`order` tinyint(1) COMMENT 'Campo que indica el orden a mostrar en la vista',
`is_active` tinyint(1) COMMENT 'Campo identificador del estado de la clasificación de la condición del puesto de trabajo',
`created_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha creación del registro o insert de la información',
`created_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que registra o inserta la información',
`updated_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha actualización de la información',
`updated_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que actualiza la información',
PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacenan las clasificaciones y condiciones de puesto de trabajo';

DROP TABLE IF EXISTS wg_customer_job_condition_questions;
CREATE TABLE wg_customer_job_condition_questions (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Campo identificador de la tabla',
`name` varchar(500) null COMMENT 'Campo pregunta',
`order` tinyint(1) COMMENT 'Campo identificador del orden a mostrar en la visualización',
`is_active` tinyint(1) COMMENT 'Campo identificador del estado de la pregunta',
`created_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha creación del registro o insert de la información',
`created_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que registra o inserta la información',
`updated_by` varchar(100) DEFAULT NULL COMMENT 'Campo fecha actualización de la información',
`updated_at` datetime DEFAULT NULL COMMENT 'Campo identificador del usuario que actualiza la información',
PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacenan las preguntas de las autoevaluaciones';

DROP TABLE IF EXISTS wg_customer_job_condition_classification_questions;
CREATE TABLE wg_customer_job_condition_classification_questions (
  id int(10) unsigned NOT NULL auto_increment COMMENT 'Campo identificador de la tabla',
  classification_id int COMMENT 'Campo identificador de la tabla de clasificación (wg_customer_job_condition_classification)',
  question_id int COMMENT 'Campo identificador de la tabla de pregunta (wg_customer_job_condition_questions)',
  work_model varchar(100) COMMENT 'Campo donde se indica el modelo de trabajo',
PRIMARY KEY (`id`) )ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacena la información de la clasificación de las preguntas de las condiciones de puestos de trabajo';

DROP TABLE IF EXISTS wg_customer_job_condition_staging;
CREATE TABLE wg_customer_job_condition_staging (
`id` int(10) unsigned NOT NULL auto_increment COMMENT 'Campo identificador de la tabla',
`customer_id` bigint(20) DEFAULT null COMMENT 'Campo identificador del cliente',
`identification_type` varchar(15) DEFAULT null COMMENT 'Campo tipo de identificación',
`document_number` varchar(15) DEFAULT null COMMENT 'Campo documento del empleado',
`registration_date` date DEFAULT null COMMENT 'Fecha cuando se genera la autoevaluación',
`work_model` varchar(30) DEFAULT null COMMENT 'Campo identificador modelo de trabajo',
`location` varchar(30) DEFAULT null COMMENT 'Campo identificador ubicación',
`job` varchar(30) DEFAULT null COMMENT 'Campo identificador cargo del empleado',
`workplace` varchar(200) DEFAULT null COMMENT 'Campo identificador lugar de trabajo',
`observation` text DEFAULT null COMMENT 'Campo donde se almacenan los errores evidenciados',
`session_id` varchar(255) DEFAULT null COMMENT 'Campo identificador session del insert a la tabla staging',
`created_by` varchar(10) DEFAULT null COMMENT 'Campo identificador usuario importa información',
`created_at` datetime DEFAULT null COMMENT 'Campo fecha creación o importación información',
`updated_by` varchar(10) DEFAULT null COMMENT 'Campo fecha actualización de la información',
`updated_at` datetime DEFAULT null COMMENT 'Campo identificador usuario actualiza la información',
`isAuthorized` varchar(10) DEFAULT null COMMENT 'Campo que indica si esta autorizado',
`isValid` varchar(10) DEFAULT null COMMENT 'Campo que indica si esta valida la inforación',
`index` varchar(10) DEFAULT null COMMENT 'Campo que indica la cantidad de registros',
PRIMARY KEY (`id`) )ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla temporal donde se almacena la información de las condiciones de puestos de trabajo';

DROP TABLE IF EXISTS wg_customer_job_condition_self_evaluation_evidences;
CREATE TABLE wg_customer_job_condition_self_evaluation_evidences
(
  `id` bigint unsigned NOT NULL auto_increment COMMENT 'Campo identificador de la tabla',
  `self_evaluation_id` bigint UNSIGNED NULL COMMENT 'Identificador de la auto evaluación',
  `classification_id` bigint UNSIGNED NULL COMMENT 'Identificador de la clasificación',
  `imageUrl` text NULL COMMENT 'Información sobre los adjuntos',
  `created_by` varchar(100) NULL COMMENT 'Creado por',
  `created_at` datetime NULL COMMENT 'Creado en',
  `updated_by` varchar(100) NULL COMMENT 'Actualizado por',
  `updated_at` datetime NULL COMMENT 'Actualizado el',
PRIMARY KEY (`id`) )ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacenan las evidencias de las autoevaluaciones';

DROP TABLE IF EXISTS wg_customer_job_condition_self_evaluation_answer_interventions;
CREATE TABLE wg_customer_job_condition_self_evaluation_answer_interventions
(
  `id` int(10) unsigned NOT NULL auto_increment COMMENT 'Campo identificador de la tabla',
  `self_evaluation_answer_id` bigint(20) unsigned COMMENT 'Campo identificador de la respuesta a la pregunta (wg_customer_job_condition_self_evaluation_answers)',
  `name` varchar(100) COMMENT 'Nombre del plan de intervención',
  `description` text COMMENT 'Descripción',
  `responsible_type` varchar(20) null COMMENT 'Tipo de usuario responsable',
  `responsible_id` int COMMENT 'Responsable',
  `budget` int COMMENT 'Presupuesto',
  `execution_date` date COMMENT 'Fecha de ejecución',
  `is_closed` tinyint COMMENT 'Estado si está abierto o no',
  `closed_at` datetime COMMENT 'Fecha en que se cerro',
  `closed_by` int COMMENT 'Usuario quien la cerro',
  `is_historical` tinyint COMMENT 'Si es un registro histórico',
  `files_info` text null COMMENT 'Información sobre los archivos adjuntos',
  `files_name` text null COMMENT 'Nombres de los archivos',
  `created_at` datetime COMMENT 'Fecha registro',
  `created_by` int COMMENT 'Creado por',
  `updated_at` datetime COMMENT 'última fecha de actualización',
  `updated_by` int COMMENT 'Actualizo por',
  PRIMARY KEY (id) )ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Tabla donde se almacenan los planes de intervención a las respuestas de la autoevaluación';

/*Alter tables*/
ALTER TABLE wg_customer_user add customer_employee_id bigint null COMMENT 'Campo identificador del empleado';

/*Inserts*/
insert into wg_customer_job_condition_classification (id, name, parent_id, `order`, is_active)
values  (1, 'CONDICIONES ERGONÓMICAS', null, 1, 1),
        (2, 'CONDICIONES AMBIENTALES', null, 2, 1),
        (3, 'CONDICIONES BIOLÓGICAS', null, 3, 1),
        (4, 'CONDICIONES LOCATIVAS', null, 4, 1),
        (5, 'CONDICIONES DE EMERGENCIA', null, 5, 1),
        (6, 'CONDICIONES ELÉCTRICAS', null, 6, 1),
        (7, 'Puesto de trabajo (espacio mobiliario, elementos de trabajo)', 1, 7, 1),
        (8, 'Iluminacion', 2, 8, 1),
        (9, 'Ruido', 2, 9, 1),
        (10, 'Eléctrico', 6, 10, 1),
        (11, 'Ventilación', 2, 11, 1),
        (12, 'Virus, hongos, bacterias, insectos y roedores', 3, 12, 1),
        (13, 'Fluidos biológicos', 3, 13, 1),
        (14, 'Pisos', 4, 14, 1),
        (15, 'Techo', 4, 15, 1),
        (16, 'Paredes', 4, 16, 1),
        (17, 'Orden y aseo', 4, 17, 1),
        (18, 'Circulación', 4, 18, 1),
        (19, 'Escaleras', 4, 19, 1),
        (20, 'Puertas', 4, 20, 1),
        (21, 'Riesgo de incendio', 5, 21, 1),
        (22, 'Equipos e instalaciones eléctricas', 6, 22, 1);

-- questions
insert into wg_customer_job_condition_questions (id, name, `order`, is_active)
values  (1, '¿La superficie de trabajo permite acomodar la pantalla, el teclado y el mouse en el mismo plano?', 1, 1),
        (2, '¿La superficie de trabajo permite la facilidad de movimiento de miembros inferiores?', 2, 1),
        (3, '¿La silla cuenta con asiento acolchado y espaldar recto?', 3, 1),
        (4, '¿Los pies quedan bien apoyados al piso?', 4, 1),
        (5, '¿Los pies quedan bien apoyados al piso?', 5, 1),
        (6, '¿El ruido interno y/o externo permite realizar las operaciones de trabajo sin ninguna nterferencia?', 6, 1),
        (7, '¿El cableado cuenta con conexión a tierra y se encuentran en buenas condiciones?', 7, 1),
        (8, '¿Los elementos de trabajo más usados se encuentran ubicados a menos de 25 cm de distancia de alcance?', 8, 1),
        (9, '¿Para el desarrollo de actividades laborales, el espacio dispuesto en la residencia cumple con las dimensiones necesarias (trabajo de oficina en posición sentado), mínimo 150 cm de ancho por 150 cm de largo?', 9, 1),
        (10, '¿La ubicación del puesto de trabajo permite movilidad en la silla? Verificar que el espacio de movilidad de la silla sea de 80 cm o más.', 10, 1),
        (11, '¿El escritorio permite ajustar el teclado de modo que se obtenga una posición cómoda al digitar, manteniendo la mano, muñeca y brazo en línea recta con buen espacio delante del teclado para descansar las manos?', 11, 1),
        (12, '¿El mouse lo ubica al lado del teclado y no en otro nivel del escritorio de modo que se pueda alcanzar fácilmente y con la muñeca recta?', 12, 1),
        (13, '¿El escritorio permite un buen espacio para los miembros inferiores y la facilidad de movimiento? Espacio de 60 cm como mínimo.', 13, 1),
        (14, '¿El área de trabajo tiene iluminación natural (ventanas)?', 14, 1),
        (15, '¿La luz natural y/o artificial le permiten visualizar la pantalla del computador sin generar fatiga visual por reflejos, vidrios o pantallas?', 15, 1),
        (16, '¿Las lámparas o luminarias del área de trabajo están libres de polvo?', 16, 1),
        (17, '¿Percibe que la intensidad lumínica en el área permite leer sin dificultad?', 17, 1),
        (18, '¿La ventana tiene protección? (persianas, black out, cortinas, películas con filtro, otras)', 18, 1),
        (19, '¿El área de trabajo tiene ventilación natural (ventanas)?', 19, 1),
        (20, '¿El área de trabajo tiene ventilación combinada (natural y artificial)?', 20, 1),
        (21, '¿El ruido externo (vehiculos, vecinos, locales comerciales, residencias e industria) permite realizar las operaciones de trabajo sin ninguna interferencia?', 21, 1),
        (22, '¿En el área de trabajo hay fuentes generadoras de ruido? Si hay presencia, describa las fuentes generadoras de ruido en la casilla de observaciones?', 22, 1),
        (23, '¿El área de trabajo en casa es un ambiente libre de virus, bacterias, hongos, insectos, roedores?', 23, 1),
        (24, '¿En el área de trabajo en casa hay contacto con fluidos biológicos (Sangre, vomito y/u otros fluidos corporales)?', 24, 1),
        (25, '¿Los pisos del área de trabajo en casa son planos?', 25, 1),
        (26, '¿El piso está libre de obstáculos y desperdicios?', 26, 1),
        (27, '¿EL material del piso está en buenas condiciones? (ausencia de huecos)', 27, 1),
        (28, '¿Las rodachinas de la silla son adaptables al piso en el área definida para teletrabajo?', 28, 1),
        (29, '¿El techo del área de trabajo en casa está en buenas condiciones? (sin humedades, grietas o comején?', 29, 1),
        (30, '¿Las paredes del área de trabajo en casa están en buenas condiciones? (libres de humedades, grietas)', 30, 1),
        (31, '¿Los cuadros, repisas o demás objetos anclados en el área de trabajo en casa son seguros?', 31, 1),
        (32, '¿En el área de trabajo en casa hay buenas prácticas de orden, limpieza y aseo?', 32, 1),
        (33, 'Si la mesa o superficie de trabajo tiene cajones, son almacenados allí los elementos de trabajo y el espacio es suficiente?', 33, 1),
        (34, '¿Los espacios destinados para la entrada y salida del área de trabajo están libres de obstáculos?', 34, 1),
        (35, '¿El ingreso al área definida para el teletrabajo es seguro? Describa en observaciones cuál es el acceso, si es por escaleras, corredor, rampa, mezzanine, balcones?', 35, 1),
        (36, '¿Las escaleras se encuentran en buen estado?', 36, 1),
        (37, '¿Las escalas y balcones cumplen con pasamanos, bandas o piso antideslizante y el tamaño de huella en la escala es suficiente para la mayoría de las personas?', 37, 1),
        (38, '¿El material y diseño de las escaleras es antideslizante?', 38, 1),
        (39, '¿Las escaleras están libres de obstáculos?', 39, 1),
        (40, '¿Las diferentes puertas que tiene el área de trabajo están en bue estado y funcionan normalmente? ¿En el área de trabajo hay buenas?', 40, 1),
        (41, '¿Conoce los procedimientos para actuar en caso de una emergencia en el lugar definido para el teletrabajo?', 41, 1),
        (42, '¿Cuenta con medios de extinción o extintor? ¿Interno y/o externos en zonas comunes?', 42, 1),
        (43, '¿Conoce el procedimiento para uso y manejo de extintores?', 43, 1),
        (44, '¿En el lugar definido para el teletrabajo hay ausencia de almacenamiento de líquidos o sólidos combustibles?', 44, 1),
        (45, '¿Los equipos en el área de trabajo en casa tienen conexión a tierra?', 45, 1),
        (46, '¿Las instalaciones eléctricas están completamente protegidas, (tomas, extensiones y enchufes) sin cables expuestos?', 46, 1),
        (47, '¿Hay ausencia de empalmes (uniones) de cables eléctricos?', 47, 1),
        (48, '¿Los enchufes del área de trabajo en casa no están sobrecargados con muchas conexiones?', 48, 1),
        (49, '¿Las cajas de interruptores están cubiertos?', 49, 1);

-- relation question by classifications
insert into wg_customer_job_condition_classification_questions (id, classification_id, question_id, work_model)
values  (1, 7, 1, 'JWM001'),
        (2, 7, 2, 'JWM001'),
        (3, 7, 3, 'JWM001'),
        (4, 7, 4, 'JWM001'),
        (5, 8, 5, 'JWM001'),
        (6, 9, 6, 'JWM001'),
        (7, 10, 7, 'JWM001'),
        (8, 7, 8, 'JWM002'),
        (9, 7, 9, 'JWM002'),
        (10, 7, 10, 'JWM002'),
        (11, 7, 11, 'JWM002'),
        (12, 7, 12, 'JWM002'),
        (13, 7, 13, 'JWM002'),
        (14, 8, 14, 'JWM002'),
        (15, 8, 15, 'JWM002'),
        (16, 8, 16, 'JWM002'),
        (17, 8, 17, 'JWM002'),
        (18, 8, 18, 'JWM002'),
        (19, 11, 19, 'JWM002'),
        (20, 11, 20, 'JWM002'),
        (21, 9, 21, 'JWM002'),
        (22, 9, 22, 'JWM002'),
        (23, 12, 23, 'JWM002'),
        (24, 13, 24, 'JWM002'),
        (25, 14, 25, 'JWM002'),
        (26, 14, 26, 'JWM002'),
        (27, 14, 27, 'JWM002'),
        (28, 14, 28, 'JWM002'),
        (29, 15, 29, 'JWM002'),
        (30, 16, 30, 'JWM002'),
        (31, 16, 31, 'JWM002'),
        (32, 17, 32, 'JWM002'),
        (33, 17, 33, 'JWM002'),
        (34, 18, 34, 'JWM002'),
        (35, 18, 35, 'JWM002'),
        (36, 19, 36, 'JWM002'),
        (37, 19, 37, 'JWM002'),
        (38, 19, 38, 'JWM002'),
        (39, 19, 39, 'JWM002'),
        (40, 20, 40, 'JWM002'),
        (41, 21, 41, 'JWM002'),
        (42, 21, 42, 'JWM002'),
        (43, 21, 43, 'JWM002'),
        (44, 21, 44, 'JWM002'),
        (45, 22, 45, 'JWM002'),
        (46, 22, 46, 'JWM002'),
        (47, 22, 47, 'JWM002'),
        (48, 22, 48, 'JWM002'),
        (49, 22, 49, 'JWM002');

alter table wg_customer_job_condition_self_evaluation
  add fully_answered tinyint null comment 'Define si una evaluación está completamente respondida' after workplace_id;


DROP TABLE IF EXISTS wg_customer_job_condition_self_evaluation_historical;

DROP TABLE IF EXISTS wg_customer_job_condition_self_evaluation_tracking;
CREATE TABLE wg_customer_job_condition_self_evaluation_tracking (
  id int UNSIGNED AUTO_INCREMENT COMMENT 'Campo unico identificador de la tabla',
  customer_id bigint UNSIGNED NULL COMMENT 'Campo identificador del cliente',
  self_evaluation_id bigint NULL COMMENT 'Id de la evaluación',
  location varchar(100) NULL COMMENT 'Lugar de trabajo',
  risk int NULL COMMENT 'Porcentaje de riesgo en su momento',
  created_at datetime NULL COMMENT 'Fecha en que se realizó el registro',
  date_evaluation datetime NULL COMMENT 'Fecha de la evalución',
  PRIMARY KEY (id)
) COMMENT 'Almacena el historico de estado de las auto evaluaciones';



create index idx_wg_customer_job_condition_customer_id on wg_customer_job_condition (customer_id);
create index idx_wg_customer_job_condition_evaluation_job_condition_id on wg_customer_job_condition_self_evaluation (job_condition_id);
create index idx_wg_customer_job_condition_evaluation_date on wg_customer_job_condition_self_evaluation (registration_date);

create index idx_wg_customer_job_condition_evaluation_answers_evaluation_id on wg_customer_job_condition_self_evaluation_answers (self_evaluation_id);
create index idx_wg_customer_job_condition_evaluation_answers_initial on wg_customer_job_condition_self_evaluation_answers (question_id);

create index idx_wg_customer_job_condition_evaluation_evidences on wg_customer_job_condition_self_evaluation_evidences (self_evaluation_id);

create index idx_wg_customer_job_condition_evaluation_interventions_answer on wg_customer_job_condition_self_evaluation_answer_interventions (self_evaluation_answer_id);
create index idx_wg_customer_job_condition_evaluation_interventions_history on wg_customer_job_condition_self_evaluation_answer_interventions (is_historical);
create index idx_wg_customer_job_condition_evaluation_interventions_closed on wg_customer_job_condition_self_evaluation_answer_interventions (is_closed);

create index idx_wg_customer_job_condition_evaluation_tracking_customer_id on wg_customer_job_condition_self_evaluation_tracking (customer_id);
create index idx_wg_customer_job_condition_evaluation_tracking_created on wg_customer_job_condition_self_evaluation_tracking (created_at);
create index idx_wg_customer_job_condition_evaluation_tracking_date_eval on wg_customer_job_condition_self_evaluation_tracking (date_evaluation);
create index idx_wg_customer_job_condition_evaluation_tracking_evaluation_id on wg_customer_job_condition_self_evaluation_tracking (self_evaluation_id);

-- 24/06/2021 
-- Procedimientos
DROP PROCEDURE IF EXISTS TL_JOB_CONDITIONS;
CREATE PROCEDURE `TL_JOB_CONDITIONS`(IN `customerId` bigint,IN `sessionId` varchar(255))
BEGIN
  /*Condiciones puestos de trabajo*/
  /*Validamos si el empleado ya esta creado si no esta creado lo insertamos*/
  INSERT INTO wg_customer_job_condition (customer_employee_id, customer_id, created_by, created_at, staging_id)
  SELECT wce.id, O.customer_id, O.created_by, O.created_at, O.id
  FROM wg_customer_job_condition_staging O
  INNER JOIN wg_customer_employee wce ON wce.customer_id = O.customer_id
  INNER JOIN wg_employee AS e ON e.id = wce.employee_id and e.documentType = O.identification_type AND e.documentNumber = O.document_number
  LEFT JOIN wg_customer_job_condition d ON d.customer_employee_id = wce.id
  WHERE O.session_id = sessionId AND d.id IS null AND O.isValid = 1
  GROUP BY wce.id;

  -- save workplaces
  INSERT INTO wg_customer_job_condition_workplace (name)
  SELECT DISTINCT O.workplace from wg_customer_job_condition_staging O
  LEFT JOIN wg_customer_job_condition_workplace d ON d.name = O.workplace
  WHERE O.session_id = sessionId AND d.id IS null AND O.isValid = 1;

  /*Almacenar autoevaluaciones*/
  insert into wg_customer_job_condition_self_evaluation (job_condition_id, registration_date, work_model, location, workplace_id, fully_answered, occupationId,state)
  SELECT jc.id, O.registration_date, O.work_model, O.location, workplace.id AS workplace, 0, O.job AS occupationId,1
  FROM wg_customer_job_condition_staging O
  INNER JOIN wg_customer_employee wce ON wce.customer_id = O.customer_id
  INNER JOIN wg_employee AS e ON e.id = wce.employee_id and e.documentType = O.identification_type AND e.documentNumber = O.document_number
  INNER JOIN wg_customer_job_condition jc ON jc.customer_employee_id = wce.id
  INNER JOIN wg_customer_job_condition_workplace workplace ON workplace.name = O.workplace
  LEFT JOIN wg_customer_job_condition_self_evaluation d ON  jc.id = d.job_condition_id
      AND d.registration_date = O.registration_date AND d.location = O.location AND d.state = 1
  WHERE O.session_id = sessionId AND d.id IS NULL AND O.isValid = 1
  GROUP BY jc.id, location;
END;