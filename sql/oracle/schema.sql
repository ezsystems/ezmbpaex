
CREATE TABLE ezx_mbpaex (
  contentobject_id INTEGER DEFAULT 0 NOT NULL,
  expirationnotification INTEGER DEFAULT -1 NOT NULL,
  expirationnotification_sent INTEGER DEFAULT 0 NOT NULL,
  password_last_updated INTEGER DEFAULT 0 NOT NULL,
  passwordlifetime INTEGER DEFAULT -1 NOT NULL,
  passwordvalidationregexp VARCHAR2(150),
  updatechildren INTEGER DEFAULT 0 NOT NULL,
  PRIMARY KEY ( contentobject_id )
);




