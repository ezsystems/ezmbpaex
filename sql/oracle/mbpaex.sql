CREATE TABLE ezx_mbpaex (
  contentobject_id integer DEFAULT 0 NOT NULL,
  passwordvalidationregexp varchar2(255) DEFAULT '',
  passwordlifetime integer  DEFAULT -1 NOT NULL,
  expirationnotification integer DEFAULT -1 NOT NULL,
  password_last_updated integer DEFAULT 0 NOT NULL,
  updatechildren integer DEFAULT 0 NOT NULL,
  expirationnotification_sent integer DEFAULT 0 NOT NULL,
  PRIMARY KEY (contentobject_id)
);

