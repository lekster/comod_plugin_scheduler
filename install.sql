--drop table plugins.scheduler_jobs
--drop table plugins.scheduler_jobs_template

CREATE TABLE plugins.scheduler_job_template
(
  scheduler_job_template_id bigserial NOT NULL,
  name character varying(255) NOT NULL,
  description character varying(255),
  hint character varying(255),
  object_id bigint NOT NULL,
  property_id bigint NOT NULL,
  start_at character varying(255) NOT NULL,
  end_at character varying(255),
  work_time bigint,
  value_at_start character varying(255),
  value_at_end  character varying(255),
  type_action smallint NOT NULL, --изменить значение, возвращать ли предыдущее и т.п.
  options text,
  next_run_time timestamp without time zone,
  is_active boolean NOT NULL DEFAULT false,
  CONSTRAINT scheduler_job_template_id_pkey PRIMARY KEY (scheduler_job_template_id),
  CONSTRAINT object_id_fkey FOREIGN KEY (object_id)
      REFERENCES objects (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT property_id_fkey FOREIGN KEY (property_id)
      REFERENCES properties (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
ALTER TABLE plugins.scheduler_job_template
  OWNER TO postgres;
GRANT ALL ON TABLE plugins.scheduler_job_template TO postgres;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE plugins.scheduler_job_template TO inform;

GRANT ALL ON TABLE plugins.scheduler_job_template_scheduler_job_template_id_seq TO postgres;
GRANT SELECT, UPDATE ON TABLE plugins.scheduler_job_template_scheduler_job_template_id_seq TO public;
GRANT SELECT, UPDATE ON TABLE plugins.scheduler_job_template_scheduler_job_template_id_seq TO inform;


CREATE TABLE plugins.scheduler_jobs
(
  scheduler_job_id bigserial NOT NULL,
  scheduler_job_template_id bigint not null,
  date_create timestamp without time zone not null default now(),
  result_code smallint,
  result_description character varying(255),
  task_start_id bigint,
  task_end_id bigint,
  CONSTRAINT scheduler_job_id_pkey PRIMARY KEY (scheduler_job_id),
  CONSTRAINT scheduler_job_template_id_fkey FOREIGN KEY (scheduler_job_template_id)
      REFERENCES plugins.scheduler_job_template (scheduler_job_template_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT task_start_id_fkey FOREIGN KEY (task_start_id)
      REFERENCES tasks (task_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT task_end_id_fkey FOREIGN KEY (task_end_id)
      REFERENCES tasks (task_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
ALTER TABLE plugins.scheduler_jobs
  OWNER TO postgres;
GRANT ALL ON TABLE plugins.scheduler_jobs TO postgres;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE plugins.scheduler_jobs TO inform;

GRANT ALL ON TABLE plugins.scheduler_jobs_scheduler_job_id_seq TO postgres;
GRANT SELECT, UPDATE ON TABLE plugins.scheduler_jobs_scheduler_job_id_seq TO public;
GRANT SELECT, UPDATE ON TABLE plugins.scheduler_jobs_scheduler_job_id_seq TO inform;