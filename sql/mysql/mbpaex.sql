CREATE TABLE ezx_mbpaex (
  contentobject_id int(11) NOT NULL default '0',
  passwordvalidationregexp varchar(150) NOT NULL default '',
  passwordlifetime int(11) NOT NULL default '-1',
  expirationnotification int(11) NOT NULL default '-1',
  password_last_updated int(11) NOT NULL DEFAULT 0,
  updatechildren int(2) NOT NULL DEFAULT 0,
  expirationnotification_sent int(2) NOT NULL DEFAULT 0,

  PRIMARY KEY  (contentobject_id)
) ENGINE=InnoDB;
