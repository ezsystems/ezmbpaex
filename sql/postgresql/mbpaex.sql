CREATE TABLE ezx_mbpaex (
  contentobject_id integer NOT NULL DEFAULT 0,
  passwordvalidationregexp character varying(150) DEFAULT '' NOT NULL,
  passwordlifetime integer  DEFAULT -1 NOT NULL,
  expirationnotification integer DEFAULT -1 NOT NULL,
  password_last_updated integer DEFAULT 0 NOT NULL,
  updatechildren integer DEFAULT 0 NOT NULL,
  expirationnotification_sent integer DEFAULT 0 NOT NULL
);

ALTER TABLE ONLY ezx_mbpaex ADD CONSTRAINT ezx_mbpaex_pkey PRIMARY KEY (contentobject_id);

