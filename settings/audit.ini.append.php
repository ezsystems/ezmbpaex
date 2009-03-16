<?php /* #?ini charset="iso-8859-1"?

#
# New audit functions defined for mbpaex operations:
#
#   user-password-change
#   user-password-change-self
#   user-password-change-self-fail
#   user-forgotpassword
#   user-forgotpassword-fail
#

[AuditSettings]

# Audit file names setting.
# The key of AuditFileNames[] is the name of audit and value is file name.
#    For example:
#    AuditFileNames[<name of audit>]=<file name>
# Always clients IP address and user names(if exist) will be logged.

# User changes another user password
AuditFileNames[user-password-change]=password_change.log

# User changes their own password
AuditFileNames[user-password-change-self]=password_change_self.log

# Failures changing own password
AuditFileNames[user-password-change-self-fail]=failed_password_change_self.log

# Password changes via forgot-password
AuditFileNames[user-forgotpassword]=forgotpassword.log

# Failures on forgot-password
AuditFileNames[user-forgotpassword-fail]=failed_forgotpassword.log

*/ ?>
