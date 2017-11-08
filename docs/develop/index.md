FlexMailer is an email delivery system for CiviCRM v4.7+ which supports
batching and events, such as `WalkBatchesEvent`, `ComposeBatchEvent` and
`SendBatchEvent`.

FlexMailer includes default listeners for these events.  The listeners behave
in basically the same way as CiviMail's traditional BAO-based delivery
system (respecting `mailerJobSize`, `mailThrottleTime`, `mailing_backend`,
`hook_civicrm_alterMailParams`, etal).  However, this arrangement allows you
change behaviors in more fine-grained ways.

> NOTE: Some examples have not been tested well. This is an early revision of the doc+code!


## Event Inspection

To see what event listeners are configured, run:

```
cv debug:event-dispatcher /flexmail/
```

## Unit tests

The headless unit tests are based on `phpunit` and `cv`. Simply run:

```
phpunit4
```

## FAQ: How do you register a listener?

The examples above use `hook_civicrm_container` to manipulate the `dispatcher`;
and, specifically, they use `addListener()` to register a single global
function.  However, you can also add multievent "subscribers" via
`addSubscriber()`, and you can register object-oriented services via
`addListenerService()` or `addSubscriberService()`.

For more discussion, see http://symfony.com/doc/current/components/event_dispatcher/introduction.html

## FAQ: Why use Symfony `EventDispatcher` instead of `CRM_Utils_Hook`?

There are several advantages of Symfony's event system -- for example, it
supports type-hinting, better in-source documentation, better
object-oriented modeling, and better refactoring.  However, that's not why
FlexMailer uses it.  Firstly, Symfony allows you to have multiple listeners
in the same module.  Secondly, when you have multiple parties influencing an
event, the EventDispatcher allows you to both (a) set priorities among them
and (b) allow one listener to veto other listeners.

Some of these characteristics are also available in CMS event systems, but
not consistently so.  The challenge of `CRM_Utils_Hook` is that it must
support the lowest-common denominator of functionality.
