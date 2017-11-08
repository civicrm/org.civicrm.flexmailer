# Change Log

## v0.2-alpha1

* Override core's `Mailing.preview` API to support rendering via
  Flexmailer events.
* (BC Break) In the class `DefaultComposer`, change the signature for
  `createMessageTemplates()` and `applyClickTracking()` to provide full
  access to the event context (`$e`).

## v0.3-alpha1

* (BC Break) Convert from internal event notation to external event (hook) notation.
    * The literal event name has changed from `civi.flexmailer.*` to `hook_civicrm_flexmailer_*`.
    * Listeners which subscribed using a constant (eg `FlexMailer::EVENT_COMPOSE`) should continue to work as before.
      However, listeners which used the string literal (eg `civi.flexmailer.compose`) are likely to break.
    * All event classes now extend `GenericHookEvent`. The main functions should continue to work as before.
      However, `GenericHookEvent` implements the magic methods (`__get()`, etal); if a customization used undeclared/unofficial properties, they could be impacted.
