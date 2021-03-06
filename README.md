# org.civicrm.flexmailer

FlexMailer (`org.civicrm.flexmailer`) is an email delivery engine for CiviCRM v4.7+.  It replaces the internal guts of CiviMail.  It is a
drop-in replacement which enables *other* extensions to provide richer email features.

**IMPORTANT**: as of CiviCRM 5.28, Flexmailer is now part of the [civicrm-core](https://github.com/civicrm/civicrm-core/commits/master/ext/flexmailer) git repository. Open issues in [dev/mail](https://lab.civicrm.org/dev/mail) and pull-requests against civicrm-core.

* [Introduction](docs/index.md)
* [Installation](docs/install.md)
* [Development](docs/develop/index.md)
    * [CheckSendableEvent](docs/develop/CheckSendableEvent.md)
    * [WalkBatchesEvent](docs/develop/WalkBatchesEvent.md)
    * [ComposeBatchEvent](docs/develop/ComposeBatchEvent.md)
    * [SendBatchEvent](docs/develop/SendBatchEvent.md)
